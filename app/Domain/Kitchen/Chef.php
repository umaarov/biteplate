<?php

declare(strict_types=1);

namespace App\Domain\Kitchen;

use App\Domain\Ordering\Order;

/**
 * The RECEIVER in the Command pattern. The chef knows *how* to carry out kitchen
 * work on an order; the command objects know *when* and *which*. Keeping the
 * know-how here means the commands stay thin and the queue stays ignorant of
 * cooking rules.
 */
final class Chef
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function startPreparing(Order $order): void
    {
        $order->beginPreparation();
    }

    public function completePreparation(Order $order): void
    {
        $order->markReady();
    }

    public function cancelOrder(Order $order, string $reason): void
    {
        $order->cancel($reason);
    }
}
