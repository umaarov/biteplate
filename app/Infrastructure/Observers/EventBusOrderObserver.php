<?php

declare(strict_types=1);

namespace App\Infrastructure\Observers;

use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderObserver;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Ordering\OrderStatusChanged;
use App\Domain\Shared\EventBus;

/**
 * Bridges the in-process Observer pattern to the message bus: every order status
 * change is republished as a {@see OrderStatusChanged} domain event onto Kafka,
 * where out-of-process consumers (analytics, external kitchen screens) can react.
 * This is the same observer concept operating across a process boundary.
 */
final class EventBusOrderObserver implements OrderObserver
{
    public function __construct(private readonly EventBus $bus)
    {
    }

    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        $this->bus->publish(new OrderStatusChanged(
            $order->id(),
            $order->tableNumber(),
            $order->waiterId(),
            $from,
            $to,
        ));
    }
}
