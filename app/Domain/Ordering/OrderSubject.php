<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

/**
 * The Subject side of the Observer pattern. {@see Order} is the concrete subject;
 * it maintains the observer list and notifies them on every state change.
 */
interface OrderSubject
{
    public function attach(OrderObserver $observer): void;

    public function detach(OrderObserver $observer): void;
}
