<?php

declare(strict_types=1);

namespace App\Domain\Kitchen\Command;

use App\Domain\Kitchen\KitchenQueue;
use App\Domain\Ordering\Order;

/**
 * Jumps an order to the front of the board (the classic "table 4 is in a hurry").
 * Undo drops it back into the exact slot it came from, so reprioritising is
 * never destructive.
 */
final class ExpediteOrderCommand implements KitchenCommand
{
    private ?int $previousIndex = null;

    public function __construct(
        private readonly KitchenQueue $queue,
        private readonly Order $order,
    ) {
    }

    public function execute(): void
    {
        $this->previousIndex = $this->queue->positionOf($this->order);
        $this->queue->moveToFront($this->order);
    }

    public function undo(): void
    {
        if ($this->previousIndex !== null) {
            $this->queue->insertAt($this->order, $this->previousIndex);
        }
    }

    public function describe(): string
    {
        return sprintf('Expedite order %s (table %d) to front', $this->order->id(), $this->order->tableNumber());
    }
}
