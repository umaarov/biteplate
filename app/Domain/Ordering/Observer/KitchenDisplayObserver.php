<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Observer;

use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderObserver;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\NotificationChannel;

/**
 * Mirrors order activity onto the kitchen display screen(s). Only reacts to the
 * transitions the kitchen cares about, ignoring the rest.
 */
final class KitchenDisplayObserver implements OrderObserver
{
    public function __construct(private readonly NotificationChannel $channel)
    {
    }

    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        $audience = 'kitchen';

        match ($to) {
            OrderStatus::SentToKitchen => $this->channel->send(
                $audience,
                sprintf('NEW ticket — order %s, table %d (%d items).', $order->id(), $order->tableNumber(), count($order->kitchenTickets())),
            ),
            OrderStatus::Cancelled => $this->channel->send(
                $audience,
                sprintf('CANCELLED — pull order %s (table %d) from the line.', $order->id(), $order->tableNumber()),
            ),
            default => null,
        };
    }
}
