<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Observer;

use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderObserver;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\NotificationChannel;

/**
 * Scenario A — the Allergy Alert System.
 *
 * The moment an order containing a high-risk allergen is sent to the kitchen,
 * this observer fans an alert out to the kitchen, the waiter and the manager
 * simultaneously. New recipients are added by registering new observers, never
 * by editing this class or the {@see Order} — exactly the extensibility the
 * product team asked for.
 */
final class AllergyAlertObserver implements OrderObserver
{
    public function __construct(private readonly NotificationChannel $channel)
    {
    }

    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        if ($to !== OrderStatus::SentToKitchen) {
            return;
        }

        $highRisk = array_filter($order->allergens(), static fn ($a) => $a->isHighRisk());

        if ($highRisk === []) {
            return;
        }

        $names = implode(', ', array_map(static fn ($a) => $a->label(), $highRisk));
        $message = sprintf('ALLERGEN ALERT — order %s (table %d) contains: %s.', $order->id(), $order->tableNumber(), $names);

        foreach (['kitchen', 'waiter:'.$order->waiterId(), 'manager'] as $audience) {
            $this->channel->send($audience, $message);
        }
    }
}
