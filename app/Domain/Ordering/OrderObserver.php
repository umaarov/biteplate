<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

/**
 * OBSERVER — implemented by anything that reacts to an order's status changing:
 * the waiter's handset, the manager dashboard, the kitchen display, the allergy
 * alert system. New reactors are added simply by implementing this interface and
 * attaching to the {@see Order}; existing code is never edited (Scenario A).
 */
interface OrderObserver
{
    public function orderStatusChanged(Order $order, OrderStatus $from, OrderStatus $to): void;
}
