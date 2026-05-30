<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Observer;

use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderObserver;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\NotificationChannel;

/**
 * Pings the waiter who owns the order when there is something for them to do —
 * chiefly when the kitchen marks it Ready to serve.
 */
final class WaiterNotifier implements OrderObserver
{
    public function __construct(private readonly NotificationChannel $channel)
    {
    }

    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        $audience = 'waiter:'.$order->waiterId();

        match ($to) {
            OrderStatus::Ready => $this->channel->send(
                $audience,
                sprintf('Order %s for table %d is READY to serve.', $order->id(), $order->tableNumber()),
            ),
            OrderStatus::Cancelled => $this->channel->send(
                $audience,
                sprintf('Order %s (table %d) was cancelled: %s', $order->id(), $order->tableNumber(), $order->cancellationReason()),
            ),
            default => null,
        };
    }
}
