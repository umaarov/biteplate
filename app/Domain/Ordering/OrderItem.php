<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\Menu\MenuComponent;
use App\Domain\Pricing\PricedLine;
use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;

/**
 * One line on an order: a {@see MenuComponent} (which may be a plain dish, a
 * decorated/customised dish, or a whole combo) together with a quantity.
 *
 * Because it holds a MenuComponent rather than a concrete dish, an order line
 * treats "Cheeseburger, no gluten, extra bacon" and "Family Combo" identically.
 */
final class OrderItem
{
    public function __construct(
        public readonly MenuComponent $component,
        public readonly int $quantity = 1,
    ) {
        if ($quantity < 1) {
            throw new DomainException('Order item quantity must be at least 1.');
        }
    }

    public function lineTotal(): Money
    {
        return $this->component->price()->multiply($this->quantity);
    }

    public function isDrink(): bool
    {
        return $this->component instanceof \App\Domain\Menu\HasCategory
            && $this->component->category()->isDrink();
    }

    /** Flatten into priced lines (one per unit) for the pricing engine. */
    public function toPricedLine(): PricedLine
    {
        return new PricedLine($this->component->name(), $this->lineTotal(), $this->isDrink());
    }
}
