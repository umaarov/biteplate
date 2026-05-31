<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Contracts\OrderRepository;
use App\Domain\Menu\ComboMeal;
use App\Domain\Menu\MenuCategory;
use App\Domain\Menu\MenuItem as DomainMenuItem;
use App\Domain\Ordering\Order as DomainOrder;
use App\Domain\Ordering\OrderItem as DomainOrderItem;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\Money;
use App\Infrastructure\Menu\PersistedMenuComponent;
use App\Models\Order as OrderModel;
use App\Models\OrderItem as OrderItemModel;
use Illuminate\Support\Facades\DB;

final class EloquentOrderRepository implements OrderRepository
{
    public function nextId(): string
    {
        return 'ORD-'.(1000 + OrderModel::count() + 1);
    }

    public function save(DomainOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $subtotal = $order->subtotal();

            OrderModel::updateOrCreate(
                ['id' => $order->id()],
                [
                    'table_number' => $order->tableNumber(),
                    'staff_id' => $order->waiterId(),
                    'status' => $order->status()->value,
                    'subtotal_minor' => $subtotal->minorUnits,
                    'currency' => $subtotal->currency,
                    'cancelled' => $order->status() === OrderStatus::Cancelled,
                    'wasteful' => $order->wasWastefullyCancelled(),
                    'cancellation_reason' => $order->cancellationReason(),
                    'placed_at' => $order->placedAt(),
                ],
            );

            OrderItemModel::where('order_id', $order->id())->delete();

            foreach ($order->items() as $item) {
                $component = $item->component;
                $tickets = $component->kitchenTickets();
                $notes = [];
                foreach ($tickets as $ticket) {
                    $notes = [...$notes, ...$ticket->notes];
                }

                OrderItemModel::create([
                    'order_id' => $order->id(),
                    'name' => $component->name(),
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $component->price()->minorUnits,
                    'currency' => $component->price()->currency,
                    'category' => $this->categoryOf($component)->value,
                    'station' => ($tickets[0]->station ?? KitchenStation::Pass)->value,
                    'is_drink' => $item->isDrink(),
                    'allergens' => array_map(static fn (Allergen $a) => $a->value, $component->allergens()),
                    'notes' => array_values(array_unique($notes)),
                    'summary' => $component->summary(),
                    'tickets' => array_map(static fn ($t) => [
                        'item' => $t->item,
                        'station' => $t->station->value,
                        'notes' => $t->notes,
                        'allergens' => array_map(static fn (Allergen $a) => $a->value, $t->allergens),
                    ], $tickets),
                ]);
            }
        });
    }

    public function find(string $id): ?DomainOrder
    {
        $model = OrderModel::with('items')->find($id);

        if ($model === null) {
            return null;
        }

        $items = [];
        foreach ($model->items as $row) {
            $tickets = array_map(
                static fn (array $t) => new \App\Domain\Shared\KitchenTicket(
                    $t['item'],
                    KitchenStation::from($t['station']),
                    $t['notes'] ?? [],
                    array_map(static fn (string $a) => Allergen::from($a), $t['allergens'] ?? []),
                ),
                $row->tickets ?? [],
            );

            $component = new PersistedMenuComponent(
                name: $row->name,
                description: '',
                price: Money::fromMinor($row->unit_price_minor, $row->currency),
                allergens: array_map(static fn (string $a) => Allergen::from($a), $row->allergens ?? []),
                station: KitchenStation::from($row->station),
                category: MenuCategory::from($row->category),
                notes: $row->notes ?? [],
                summaryText: $row->summary,
                tickets: $tickets,
            );
            $items[] = new DomainOrderItem($component, $row->quantity);
        }

        return DomainOrder::reconstitute(
            id: $model->id,
            tableNumber: $model->table_number,
            waiterId: $model->staff_id,
            placedAt: ($model->placed_at ?? $model->created_at)->toDateTimeImmutable(),
            status: OrderStatus::from($model->status),
            items: $items,
            wastedOnCancel: $model->wasteful,
            cancellationReason: $model->cancellation_reason,
        );
    }

    public function draftForTable(int $tableNumber): ?DomainOrder
    {
        $id = OrderModel::query()
            ->where('table_number', $tableNumber)
            ->where('status', OrderStatus::Draft->value)
            ->latest('created_at')
            ->value('id');

        return $id === null ? null : $this->find($id);
    }

    public function billableForTable(int $tableNumber): array
    {
        $ids = OrderModel::query()
            ->where('table_number', $tableNumber)
            ->whereIn('status', [
                OrderStatus::SentToKitchen->value,
                OrderStatus::InPreparation->value,
                OrderStatus::Ready->value,
                OrderStatus::Served->value,
            ])
            ->orderBy('placed_at')
            ->pluck('id');

        return array_values(array_filter(array_map(fn ($id) => $this->find($id), $ids->all())));
    }

    public function activeForKitchen(): array
    {
        $ids = OrderModel::query()
            ->whereIn('status', [
                OrderStatus::SentToKitchen->value,
                OrderStatus::InPreparation->value,
                OrderStatus::Ready->value,
            ])
            ->orderBy('placed_at')
            ->pluck('id');

        return array_values(array_filter(array_map(fn ($id) => $this->find($id), $ids->all())));
    }

    public function allBillable(): array
    {
        $ids = OrderModel::query()
            ->whereIn('status', [
                OrderStatus::SentToKitchen->value,
                OrderStatus::InPreparation->value,
                OrderStatus::Ready->value,
                OrderStatus::Served->value,
            ])
            ->orderBy('table_number')
            ->pluck('id');

        return array_values(array_filter(array_map(fn ($id) => $this->find($id), $ids->all())));
    }

    private function categoryOf(\App\Domain\Menu\MenuComponent $component): MenuCategory
    {
        return match (true) {
            $component instanceof DomainMenuItem => $component->category(),
            $component instanceof ComboMeal => MenuCategory::Combo,
            default => MenuCategory::Main,
        };
    }
}
