<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Billing\SplitBillCalculator;
use App\Domain\History\OrderHistoryLog;
use App\Domain\History\OrderLineRecord;
use App\Domain\History\OrderRecord;
use App\Domain\Kitchen\Chef;
use App\Domain\Kitchen\Command\PrepareOrderCommand;
use App\Domain\Kitchen\KitchenQueue;
use App\Domain\Menu\Beverage;
use App\Domain\Menu\ComboMeal;
use App\Domain\Menu\Customization\ExtraAddOn;
use App\Domain\Menu\MainCourse;
use App\Domain\Menu\MenuCategory;
use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Pricing\HappyHourPricing;
use App\Domain\Pricing\PricedLine;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\StandardPricing;
use App\Domain\Pricing\TimeOfDayPricingResolver;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;
use App\Domain\Staff\Cashier;
use App\Domain\Staff\Manager;
use App\Domain\Staff\Permission;
use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Pure-domain tests — they extend PHPUnit's TestCase, not Laravel's, precisely to
 * prove the design-pattern core has zero framework dependencies.
 */
final class DomainPatternsTest extends TestCase
{
    public function test_money_is_immutable_and_exact(): void
    {
        $ten = Money::of(10);
        $ten->add(Money::of(5));
        self::assertSame(1000, $ten->minorUnits, 'add() must not mutate the original');
        self::assertSame('£8.00', Money::of(10)->subtract(Money::of(2))->format());
    }

    public function test_decorator_adds_price_allergen_and_prep_note(): void
    {
        $burger = new MainCourse('B', 'Burger', '', Money::of(10), [Allergen::Gluten]);
        $decorated = new ExtraAddOn($burger, 'Bacon', Money::of(2), Allergen::Soy);

        self::assertSame(1200, $decorated->price()->minorUnits);
        self::assertContains(Allergen::Soy, $decorated->allergens());
        self::assertSame('+ Bacon', $decorated->kitchenTickets()[0]->notes[0]);
    }

    public function test_composite_prices_and_routes_uniformly(): void
    {
        $combo = (new ComboMeal('Meal', '', 10.0))
            ->add(new MainCourse('B', 'Burger', '', Money::of(10)))
            ->add(new Beverage('C', 'Cola', '', Money::of(2.50)));

        self::assertSame(1125, $combo->price()->minorUnits, '12.50 less 10% bundle');
        self::assertCount(2, $combo->kitchenTickets());
        self::assertSame(MenuCategory::Combo, $combo->category());
    }

    public function test_strategy_is_swappable_at_runtime(): void
    {
        $context = new PricingContext([new PricedLine('Burger', Money::of(20))]);

        self::assertSame(2000, (new StandardPricing())->calculate($context)->total()->minorUnits);
        self::assertSame(1600, (new HappyHourPricing(20))->calculate($context)->total()->minorUnits);
    }

    public function test_time_resolver_picks_quiet_hours_and_weekend(): void
    {
        $resolver = new TimeOfDayPricingResolver();
        self::assertStringContainsString('Happy Hour', $resolver->resolveFor(new DateTimeImmutable('2026-05-31 16:00'))->name());
        self::assertStringContainsString('Weekend', $resolver->resolveFor(new DateTimeImmutable('2026-05-30 20:00'))->name());
    }

    public function test_table_state_machine_enforces_legal_transitions(): void
    {
        $table = new Table(1, 4);
        $table->reserve();
        $table->seat(2);
        self::assertSame(TableStatus::Occupied, $table->status());

        $this->expectException(DomainException::class);
        $table->reserve(); // cannot reserve an occupied table
    }

    public function test_order_rejects_illegal_lifecycle_jump(): void
    {
        $order = new Order('O1', 1, 'EMP');
        $order->addItem(new MainCourse('B', 'Burger', '', Money::of(10)));

        $this->expectException(DomainException::class);
        $order->serve(); // draft cannot jump straight to served
    }

    public function test_command_prepare_then_undo_restores_status(): void
    {
        $order = new Order('O2', 1, 'EMP');
        $order->addItem(new MainCourse('B', 'Burger', '', Money::of(10)));
        $order->sendToKitchen();

        $queue = new KitchenQueue();
        $queue->run(new PrepareOrderCommand(new Chef('C', 'Marco'), $order));
        self::assertSame(OrderStatus::InPreparation, $order->status());

        $queue->undoLast();
        self::assertSame(OrderStatus::SentToKitchen, $order->status());
    }

    public function test_singleton_history_is_shared_and_iterable(): void
    {
        OrderHistoryLog::reset();
        $log = OrderHistoryLog::instance();
        $log->append(new OrderRecord('O', 1, 'E', [
            new OrderLineRecord('Burger', 2, Money::of(20), MenuCategory::Main),
        ], Money::of(20), new DateTimeImmutable()));

        self::assertSame($log, OrderHistoryLog::instance());
        self::assertSame(1, iterator_count($log->getIterator()));
        self::assertSame('Burger', $log->mostFrequentItem()['name']);
    }

    public function test_split_bill_distributes_pennies_exactly(): void
    {
        $shares = (new SplitBillCalculator())->split(Money::of(10), 3);
        $sum = array_reduce($shares, fn (int $c, Money $m) => $c + $m->minorUnits, 0);
        self::assertSame(1000, $sum);
        self::assertSame(334, $shares[0]->minorUnits);
    }

    public function test_role_permissions_are_polymorphic(): void
    {
        self::assertTrue((new Manager('M', 'Boss'))->can(Permission::ManageKitchenQueue));
        self::assertTrue((new Cashier('C', 'Dana'))->can(Permission::CloseBill));
        self::assertFalse((new Cashier('C', 'Dana'))->can(Permission::ManageKitchenQueue));
    }
}
