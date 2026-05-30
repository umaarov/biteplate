<?php

declare(strict_types=1);

namespace App\Domain\Kitchen\Command;

use App\Domain\Kitchen\Chef;
use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderStatus;

/**
 * Tells a {@see Chef} to start preparing an order. On undo it rolls the order
 * back to whatever status it held before preparation began.
 */
final class PrepareOrderCommand implements KitchenCommand
{
    private ?OrderStatus $previousStatus = null;

    public function __construct(
        private readonly Chef $chef,
        private readonly Order $order,
    ) {
    }

    public function execute(): void
    {
        $this->previousStatus = $this->order->status();
        $this->chef->startPreparing($this->order);
    }

    public function undo(): void
    {
        if ($this->previousStatus !== null) {
            $this->order->restoreStatus($this->previousStatus);
        }
    }

    public function describe(): string
    {
        return sprintf('Prepare order %s (table %d) — %s', $this->order->id(), $this->order->tableNumber(), $this->chef->name());
    }
}
