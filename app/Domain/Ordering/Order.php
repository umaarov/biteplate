<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * The aggregate at the heart of BitePlate, and the OBSERVER pattern's concrete
 * subject. An order:
 *
 *  - owns its line items and enforces that they may only change while Draft;
 *  - guards every lifecycle move through {@see OrderStatus} so illegal jumps are
 *    impossible;
 *  - notifies all attached {@see OrderObserver}s on each transition, which is how
 *    the waiter handset, manager dashboard, kitchen display and allergy alert all
 *    react without the order knowing they exist.
 */
final class Order implements OrderSubject
{
    /** @var list<OrderItem> */
    private array $items = [];

    /** @var list<OrderObserver> */
    private array $observers = [];

    private OrderStatus $status = OrderStatus::Draft;

    private bool $wastedOnCancel = false;

    private ?string $cancellationReason = null;

    public function __construct(
        private readonly string $id,
        private readonly int $tableNumber,
        private readonly string $waiterId,
        private readonly DateTimeImmutable $placedAt = new DateTimeImmutable(),
    ) {
    }

    /**
     * Rebuild an order from persisted state. Used only by the repository: it
     * restores the saved lifecycle status directly (the forward-only guards are
     * for live transitions, not for hydration).
     *
     * @param list<OrderItem> $items
     */
    public static function reconstitute(
        string $id,
        int $tableNumber,
        string $waiterId,
        DateTimeImmutable $placedAt,
        OrderStatus $status,
        array $items,
        bool $wastedOnCancel = false,
        ?string $cancellationReason = null,
    ): self {
        $order = new self($id, $tableNumber, $waiterId, $placedAt);
        $order->items = array_values($items);
        $order->status = $status;
        $order->wastedOnCancel = $wastedOnCancel;
        $order->cancellationReason = $cancellationReason;

        return $order;
    }

    // --- Identity -----------------------------------------------------------

    public function id(): string
    {
        return $this->id;
    }

    public function tableNumber(): int
    {
        return $this->tableNumber;
    }

    public function waiterId(): string
    {
        return $this->waiterId;
    }

    public function placedAt(): DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function wasWastefullyCancelled(): bool
    {
        return $this->wastedOnCancel;
    }

    // --- Observer wiring -----------------------------------------------------

    public function attach(OrderObserver $observer): void
    {
        foreach ($this->observers as $existing) {
            if ($existing === $observer) {
                return;
            }
        }

        $this->observers[] = $observer;
    }

    public function detach(OrderObserver $observer): void
    {
        $this->observers = array_values(
            array_filter($this->observers, static fn (OrderObserver $o) => $o !== $observer)
        );
    }

    // --- Line items ----------------------------------------------------------

    public function addItem(MenuComponent $component, int $quantity = 1): OrderItem
    {
        $this->assertModifiable();
        $item = new OrderItem($component, $quantity);
        $this->items[] = $item;

        return $item;
    }

    public function removeItemAt(int $index): void
    {
        $this->assertModifiable();

        if (! array_key_exists($index, $this->items)) {
            throw new DomainException("No order line at position {$index}.");
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function subtotal(): Money
    {
        $total = Money::zero();

        foreach ($this->items as $item) {
            $total = $total->add($item->lineTotal());
        }

        return $total;
    }

    /** @return list<\App\Domain\Pricing\PricedLine> */
    public function pricedLines(): array
    {
        return array_map(static fn (OrderItem $i) => $i->toPricedLine(), $this->items);
    }

    /** @return list<\App\Domain\Shared\Allergen> Distinct allergens across the whole order. */
    public function allergens(): array
    {
        $all = [];

        foreach ($this->items as $item) {
            foreach ($item->component->allergens() as $allergen) {
                $all[$allergen->value] = $allergen;
            }
        }

        return array_values($all);
    }

    /** @return list<\App\Domain\Shared\KitchenTicket> */
    public function kitchenTickets(): array
    {
        $tickets = [];

        foreach ($this->items as $item) {
            for ($i = 0; $i < $item->quantity; $i++) {
                foreach ($item->component->kitchenTickets() as $ticket) {
                    $tickets[] = $ticket;
                }
            }
        }

        return $tickets;
    }

    // --- Lifecycle -----------------------------------------------------------

    public function sendToKitchen(): void
    {
        if ($this->isEmpty()) {
            throw new DomainException('Cannot send an empty order to the kitchen.');
        }

        $this->transitionTo(OrderStatus::SentToKitchen);
    }

    public function beginPreparation(): void
    {
        $this->transitionTo(OrderStatus::InPreparation);
    }

    public function markReady(): void
    {
        $this->transitionTo(OrderStatus::Ready);
    }

    public function serve(): void
    {
        $this->transitionTo(OrderStatus::Served);
    }

    public function cancel(string $reason): void
    {
        if (trim($reason) === '') {
            throw new DomainException('A cancellation reason is required.');
        }

        $this->wastedOnCancel = $this->status->cancellationWouldWaste();
        $this->cancellationReason = $reason;
        $this->transitionTo(OrderStatus::Cancelled);
    }

    /**
     * Reinstate a previous lifecycle state. Intentionally bypasses the
     * forward-only guard — it exists solely so the Command pattern's undo()
     * (see {@see \App\Domain\Kitchen\Command}) can roll a kitchen action back,
     * and so it still notifies observers of the reversal.
     */
    public function restoreStatus(OrderStatus $previous): void
    {
        if ($previous === $this->status) {
            return;
        }

        $current = $this->status;
        $this->status = $previous;

        if ($previous !== OrderStatus::Cancelled) {
            $this->wastedOnCancel = false;
            $this->cancellationReason = null;
        }

        $this->notify($current, $previous);
    }

    private function transitionTo(OrderStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new DomainException(
                "Illegal order transition: {$this->status->value} → {$next->value}."
            );
        }

        $previous = $this->status;
        $this->status = $next;
        $this->notify($previous, $next);
    }

    private function notify(OrderStatus $from, OrderStatus $to): void
    {
        foreach ($this->observers as $observer) {
            $observer->orderStatusChanged($this, $from, $to);
        }
    }

    private function assertModifiable(): void
    {
        if (! $this->status->isModifiable()) {
            throw new DomainException(
                "Order {$this->id} is {$this->status->value} and can no longer be modified."
            );
        }
    }
}
