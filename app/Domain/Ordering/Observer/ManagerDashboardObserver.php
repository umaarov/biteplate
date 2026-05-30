<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Observer;

use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderObserver;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\NotificationChannel;

/**
 * Surfaces management-relevant events on the manager dashboard. In particular it
 * escalates wasteful cancellations — orders killed after the kitchen had already
 * started cooking — which feed the waste metric in the end-of-night report.
 */
final class ManagerDashboardObserver implements OrderObserver
{
    public function __construct(private readonly NotificationChannel $channel)
    {
    }

    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        if ($to === OrderStatus::Cancelled && $order->wasWastefullyCancelled()) {
            $this->channel->send(
                'manager',
                sprintf(
                    'WASTE ALERT — order %s (table %d) cancelled after preparation began. Reason: %s',
                    $order->id(),
                    $order->tableNumber(),
                    $order->cancellationReason(),
                ),
            );
        }
    }
}
