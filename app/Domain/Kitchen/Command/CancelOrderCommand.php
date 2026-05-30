<?php

declare(strict_types=1);

namespace App\Domain\Kitchen\Command;

use App\Domain\Kitchen\Chef;
use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderStatus;

/**
 * Cancels an order at the kitchen's request. Undo reinstates the order to its
 * pre-cancellation status — the safety net for an accidental cancel.
 */
final class CancelOrderCommand implements KitchenCommand
{
    private ?OrderStatus $previousStatus = null;

    public function __construct(
        private readonly Chef $chef,
        private readonly Order $order,
        private readonly string $reason,
    ) {
    }

    public function execute(): void
    {
        $this->previousStatus = $this->order->status();
        $this->chef->cancelOrder($this->order, $this->reason);
    }

    public function undo(): void
    {
        if ($this->previousStatus !== null) {
            $this->order->restoreStatus($this->previousStatus);
        }
    }

    public function describe(): string
    {
        return sprintf('Cancel order %s (table %d) — %s', $this->order->id(), $this->order->tableNumber(), $this->reason);
    }
}
