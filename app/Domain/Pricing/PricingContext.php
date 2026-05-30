<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * Everything a pricing strategy is allowed to see. Bundling the inputs into one
 * immutable context means new pricing factors (a voucher code, a table region)
 * can be added without changing the {@see PricingStrategy} signature.
 */
final readonly class PricingContext
{
    /**
     * @param list<PricedLine> $lines
     */
    public function __construct(
        public array $lines,
        public int $partySize = 1,
        public LoyaltyTier $loyaltyTier = LoyaltyTier::None,
        private ?DateTimeImmutable $at = null,
    ) {
    }

    public function subtotal(): Money
    {
        $total = Money::zero();

        foreach ($this->lines as $line) {
            $total = $total->add($line->price);
        }

        return $total;
    }

    /** The cheapest drink on the order, or null if there are none. */
    public function cheapestDrink(): ?PricedLine
    {
        $drinks = array_filter($this->lines, static fn (PricedLine $l) => $l->isDrink);

        if ($drinks === []) {
            return null;
        }

        usort($drinks, static fn (PricedLine $a, PricedLine $b) => $a->price->minorUnits <=> $b->price->minorUnits);

        return $drinks[0];
    }

    public function at(): DateTimeImmutable
    {
        return $this->at ?? new DateTimeImmutable();
    }
}
