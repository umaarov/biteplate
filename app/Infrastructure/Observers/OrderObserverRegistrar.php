<?php

declare(strict_types=1);

namespace App\Infrastructure\Observers;

use App\Domain\Ordering\Observer\AllergyAlertObserver;
use App\Domain\Ordering\Observer\KitchenDisplayObserver;
use App\Domain\Ordering\Observer\ManagerDashboardObserver;
use App\Domain\Ordering\Observer\WaiterNotifier;
use App\Domain\Ordering\Order;
use App\Domain\Shared\EventBus;
use App\Domain\Shared\NotificationChannel;

/**
 * Single place that wires the standard observer set onto an order. Adding a new
 * reactor (a loyalty-points crediter, a WhatsApp notifier) is a one-line change
 * here — and nowhere else (Scenario A / B extensibility).
 */
final class OrderObserverRegistrar
{
    public function __construct(
        private readonly NotificationChannel $channel,
        private readonly EventBus $bus,
    ) {
    }

    public function attachAll(Order $order): Order
    {
        $order->attach(new WaiterNotifier($this->channel));
        $order->attach(new KitchenDisplayObserver($this->channel));
        $order->attach(new ManagerDashboardObserver($this->channel));
        $order->attach(new AllergyAlertObserver($this->channel));
        $order->attach(new EventBusOrderObserver($this->bus));

        return $order;
    }
}
