<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\Money;

/**
 * The outcome of applying a {@see PricingStrategy}. A positive discount reduces
 * the bill; a negative discount is a surcharge (e.g. the weekend surcharge in
 * Scenario B). The notes explain to the cashier and the customer exactly what
 * was applied — never a silent price change.
 */
final readonly class PricingResult
{
    /**
     * @param list<string> $notes
     */
    public function __construct(
        public string $strategy,
        public Money $subtotal,
        public Money $discount,
        public array $notes = [],
    ) {
    }

    public function total(): Money
    {
        return $this->subtotal->subtract($this->discount);
    }
}
