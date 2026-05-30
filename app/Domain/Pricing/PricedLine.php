<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\Money;

/**
 * A flattened view of one order line handed to the pricing engine. Deliberately
 * decoupled from {@see \App\Domain\Ordering\OrderItem} so a {@see PricingStrategy}
 * can be unit-tested with no order, no table and no database in sight.
 */
final readonly class PricedLine
{
    public function __construct(
        public string $name,
        public Money $price,
        public bool $isDrink = false,
    ) {
    }
}
