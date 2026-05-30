<?php

declare(strict_types=1);

namespace App\Livewire\Orders;

use App\Application\Auth\CurrentStaff;
use App\Application\Contracts\MenuRepository;
use App\Application\Contracts\OrderRepository;
use App\Application\Services\MenuCustomizationCatalog;
use App\Application\Services\OrderService;
use App\Domain\Shared\DomainException;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Take and customise an order. Picking a dish builds a {@see \App\Domain\Menu\MenuItem}
 * via the Factory; each extra/allergen/special wraps it in a Decorator; sending
 * confirms the order, firing the Observer fan-out and writing the audit log.
 */
#[Layout('layouts.app')]
final class OrderBuilder extends Component
{
    public int $table;

    public string $orderId = '';

    // Customisation panel state.
    public ?string $selectedSku = null;

    public string $selectedName = '';

    /** @var list<string> */
    public array $extras = [];

    /** @var list<string> */
    public array $avoid = [];

    public string $special = '';

    public int $qty = 1;

    public ?string $error = null;

    public function mount(int $table, CurrentStaff $current, OrderService $orders): void
    {
        $this->table = $table;
        $staff = $current->staff();
        $this->orderId = $orders->openTab($table, $staff?->id() ?? 'EMP-UNKNOWN')->id();
    }

    public function addQuick(string $sku): void
    {
        $this->run(fn (OrderService $o) => $o->addItem($this->orderId, $sku, 1));
    }

    public function openCustomize(string $sku, string $name): void
    {
        $this->selectedSku = $sku;
        $this->selectedName = $name;
        $this->extras = [];
        $this->avoid = [];
        $this->special = '';
        $this->qty = 1;
    }

    public function cancelCustomize(): void
    {
        $this->selectedSku = null;
    }

    public function addCustom(): void
    {
        $sku = $this->selectedSku;
        if ($sku === null) {
            return;
        }

        $this->run(function (OrderService $o) use ($sku): void {
            $o->addItem($this->orderId, $sku, max(1, $this->qty), [
                'extras' => $this->extras,
                'avoid' => $this->avoid,
                'special' => $this->special,
            ]);
            $this->selectedSku = null;
        });
    }

    public function removeItem(int $index): void
    {
        $this->run(fn (OrderService $o) => $o->removeItem($this->orderId, $index));
    }

    public function send(OrderService $orders): mixed
    {
        $this->error = null;
        try {
            $orders->sendToKitchen($this->orderId);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        session()->flash('status', "Order {$this->orderId} sent to the kitchen.");

        return $this->redirectRoute('floor', navigate: true);
    }

    private function run(callable $action): void
    {
        $this->error = null;
        try {
            $action(app(OrderService::class));
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(MenuRepository $menu, OrderRepository $orders, MenuCustomizationCatalog $catalog): View
    {
        $grouped = Collection::make($menu->active(config('biteplate.branch')))
            ->groupBy(fn ($item) => $item->category);

        return view('livewire.orders.order-builder', [
            'menuGroups' => $grouped,
            'order' => $orders->find($this->orderId),
            'extrasCatalog' => $catalog->extras(),
            'allergenOptions' => $catalog->allergenOptions(),
        ]);
    }
}
