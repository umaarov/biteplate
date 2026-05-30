<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\MenuRepository;
use App\Application\Contracts\OrderHistoryRepository;
use App\Application\Contracts\OrderRepository;
use App\Application\Contracts\TableRepository;
use App\Domain\History\OrderHistoryLog;
use App\Domain\History\OrderLineRecord;
use App\Domain\History\OrderRecord;
use App\Domain\Menu\Customization\AllergenRequirement;
use App\Domain\Menu\Customization\ExtraAddOn;
use App\Domain\Menu\Customization\SpecialPreparation;
use App\Domain\Menu\MenuCategory;
use App\Domain\Menu\MenuComponent;
use App\Domain\Ordering\Order;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;
use App\Infrastructure\Observers\OrderObserverRegistrar;
use DateTimeImmutable;

/**
 * Use-cases for the order lifecycle up to kitchen hand-off. Orchestrates the
 * domain aggregate, the menu (Factory + Decorator), persistence and — on
 * confirmation — the audit log (Singleton). Pure orchestration: no business
 * rules live here, they live in the domain.
 */
final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly MenuRepository $menu,
        private readonly TableRepository $tables,
        private readonly OrderHistoryRepository $history,
        private readonly MenuCustomizationCatalog $catalog,
        private readonly OrderObserverRegistrar $observers,
    ) {
    }

    public function startDraft(int $tableNumber, string $staffId): Order
    {
        $order = new Order($this->orders->nextId(), $tableNumber, $staffId);
        $this->orders->save($order);

        return $order;
    }

    /** Reuse the table's open draft if there is one, otherwise start a new tab. */
    public function openTab(int $tableNumber, string $staffId): Order
    {
        return $this->orders->draftForTable($tableNumber) ?? $this->startDraft($tableNumber, $staffId);
    }

    /**
     * @param array{extras?: list<string>, avoid?: list<string>, special?: ?string} $customizations
     */
    public function addItem(string $orderId, string $sku, int $quantity, array $customizations = []): Order
    {
        $order = $this->require($orderId);
        $component = $this->buildComponent($sku, $customizations);
        $order->addItem($component, $quantity);
        $this->orders->save($order);

        return $order;
    }

    public function removeItem(string $orderId, int $index): Order
    {
        $order = $this->require($orderId);
        $order->removeItemAt($index);
        $this->orders->save($order);

        return $order;
    }

    /**
     * Confirm the order: attach observers (fan-out notifications + Kafka event),
     * transition to the kitchen, persist, and write the immutable audit record.
     */
    public function sendToKitchen(string $orderId): Order
    {
        $order = $this->require($orderId);
        $this->observers->attachAll($order);
        $order->sendToKitchen();
        $this->orders->save($order);
        $this->recordHistory($order);

        return $order;
    }

    public function cancelDraft(string $orderId, string $reason): Order
    {
        $order = $this->require($orderId);
        $this->observers->attachAll($order);
        $order->cancel($reason);
        $this->orders->save($order);

        return $order;
    }

    private function buildComponent(string $sku, array $customizations): MenuComponent
    {
        $component = $this->menu->leaf($sku);

        foreach ($customizations['extras'] ?? [] as $extraKey) {
            $extra = $this->catalog->findExtra($extraKey);
            if ($extra === null) {
                continue;
            }
            $component = new ExtraAddOn(
                $component,
                $extra['label'],
                Money::fromMinor($extra['surcharge_minor']),
                $extra['allergen'] !== null ? Allergen::from($extra['allergen']) : null,
            );
        }

        foreach ($customizations['avoid'] ?? [] as $allergenValue) {
            $allergen = Allergen::tryFrom($allergenValue);
            if ($allergen !== null) {
                $component = new AllergenRequirement($component, $allergen);
            }
        }

        $special = trim((string) ($customizations['special'] ?? ''));
        if ($special !== '') {
            $component = new SpecialPreparation($component, $special);
        }

        return $component;
    }

    private function recordHistory(Order $order): void
    {
        $table = $this->tables->find($order->tableNumber());
        $covers = $table?->partySize() ?? 1;

        $lines = array_map(
            fn ($item) => new OrderLineRecord(
                $item->component->name(),
                $item->quantity,
                $item->lineTotal(),
                $this->categoryOf($item->component),
            ),
            $order->items(),
        );

        $record = new OrderRecord(
            orderId: $order->id(),
            tableNumber: $order->tableNumber(),
            staffId: $order->waiterId(),
            lines: $lines,
            total: $order->subtotal(),
            placedAt: new DateTimeImmutable(),
            covers: $covers,
        );

        $this->history->append($record);
        OrderHistoryLog::instance()->append($record);
    }

    private function categoryOf(MenuComponent $component): MenuCategory
    {
        return $component instanceof \App\Domain\Menu\HasCategory
            ? $component->category()
            : MenuCategory::Main;
    }

    private function require(string $orderId): Order
    {
        return $this->orders->find($orderId) ?? throw new DomainException("Order {$orderId} not found.");
    }
}
