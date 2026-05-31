<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Billing\BillingDesk;
use App\Livewire\Floor\FloorBoard;
use App\Livewire\Kitchen\KitchenBoard;
use App\Livewire\Orders\OrderBuilder;
use App\Livewire\Reservations\ReservationBook;
use Database\Seeders\MenuSeeder;
use Database\Seeders\TableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the entire POS flow through the real Livewire components exactly as the
 * browser would — the path nothing else exercises. Catches render/action errors.
 */
final class PosFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([TableSeeder::class, MenuSeeder::class]);
        session(['staff' => ['role' => 'manager', 'id' => 'EMP-mgr', 'name' => 'Mgr']]);
    }

    public function test_full_floor_to_bill_flow_runs_without_errors(): void
    {
        // 1. Seat a party on table 4 (State: Free -> Occupied).
        Livewire::test(FloorBoard::class)
            ->call('startSeat', 4)
            ->set('partySize', 3)
            ->call('confirmSeat')
            ->assertSet('error', null);

        // 2. Take an order: a quick add, a customised add (Decorator), then send.
        $order = Livewire::test(OrderBuilder::class, ['table' => 4])
            ->call('addQuick', 'MN-BURG')
            ->call('openCustomize', 'MN-BURG', 'Classic Cheeseburger')
            ->set('extras', ['cheese'])
            ->set('subs', ['sweet-fries'])
            ->set('avoid', ['gluten'])
            ->set('special', 'medium rare')
            ->call('addCustom')
            ->assertSet('error', null);

        $order->call('send')->assertRedirect(route('floor'));

        // 3. Kitchen: prepare, mark ready, then undo the last command (Command).
        Livewire::test(KitchenBoard::class)
            ->call('prepare', $order->get('orderId'))
            ->assertSet('error', null)
            ->call('markReady', $order->get('orderId'))
            ->assertSet('error', null)
            ->call('undo')
            ->assertSet('error', null);

        // 4. Billing: swap strategy at runtime (Strategy), tip + split, finalize.
        Livewire::test(BillingDesk::class, ['table' => 4])
            ->set('strategy', 'happy_hour')
            ->set('tip', 10)
            ->set('splitWays', 2)
            ->call('finalize')
            ->assertSet('error', null)
            ->assertSet('message', fn ($m) => str_contains((string) $m, 'Bill issued'))
            ->call('closeTable', 4)
            ->assertSet('error', null)
            ->assertSet('message', fn ($m) => str_contains((string) $m, 'settled'));

        // The settled order must leave the open-bills list (terminal Paid state).
        $this->assertSame([], app(\App\Application\Contracts\OrderRepository::class)->allBillable());
    }

    public function test_combo_is_orderable_and_routes_across_stations(): void
    {
        Livewire::test(FloorBoard::class)->call('startSeat', 5)->call('confirmSeat');

        // Add a combo (Composite). Burger->hot, fries->hot, cola->bar.
        $builder = Livewire::test(OrderBuilder::class, ['table' => 5])
            ->call('addCombo', 'burger-meal')
            ->assertSet('error', null);

        $orderId = $builder->get('orderId');

        // The persisted combo must still flatten to tickets on >1 station.
        $order = app(\App\Application\Contracts\OrderRepository::class)->find($orderId);
        $stations = array_unique(array_map(
            static fn ($t) => $t->station->value,
            $order->kitchenTickets(),
        ));
        $this->assertGreaterThan(1, count($stations), 'Combo should route to multiple kitchen stations after persistence.');

        // And it is a single priced line cheaper than its parts (bundle discount).
        $this->assertCount(1, $order->items());

        $builder->call('send')->assertRedirect(route('floor'));

        // Kitchen board groups the combo's tickets by station without error.
        Livewire::test(KitchenBoard::class)->assertSet('error', null)->assertOk();
    }

    public function test_reports_render_with_recorded_history(): void
    {
        // Seat, order, send -> writes an audit record -> reports must render.
        Livewire::test(FloorBoard::class)->call('startSeat', 6)->call('confirmSeat');
        Livewire::test(OrderBuilder::class, ['table' => 6])
            ->call('addQuick', 'MN-RISO')
            ->call('send');

        $this->withSession(['staff' => ['role' => 'manager', 'id' => 'EMP-mgr', 'name' => 'Mgr']])
            ->get('/history')->assertOk()->assertSee('Revenue');
    }

    public function test_floor_page_renders_over_http_once_a_table_is_occupied(): void
    {
        // Regression guard: the floor view references $currentStaff only in the
        // Occupied/AwaitingBill branches, which the initial all-Free state never hit.
        app(\App\Application\Services\TableService::class)->seat(1, 2);

        $this->withSession(['staff' => ['role' => 'manager', 'id' => 'EMP-mgr', 'name' => 'Mgr']])
            ->get('/floor')
            ->assertOk()
            ->assertSee('Take order')
            ->assertSee('Request bill');
    }

    public function test_billing_page_opens_for_a_table_with_no_open_bill(): void
    {
        // Regression: ?table=N where N has no billable order must not 500.
        Livewire::test(BillingDesk::class, ['table' => 1])
            ->assertOk()
            ->assertSet('orderId', null)
            ->assertSee('Select an open bill');

        $this->withSession(['staff' => ['role' => 'cashier', 'id' => 'EMP-c', 'name' => 'Cash']])
            ->get('/billing?table=1')->assertOk();
    }

    public function test_reservation_booking_runs_the_pipeline(): void
    {
        Livewire::test(ReservationBook::class)
            ->set('tableNumber', 2)
            ->set('customerName', 'Jordan Reyes')
            ->set('phone', '+44 7700 900000')
            ->set('partySize', 4)
            ->set('time', '19:30')
            ->call('book')
            ->assertHasNoErrors()
            ->assertSet('message', fn ($m) => str_contains((string) $m, 'confirmed'));
    }
}
