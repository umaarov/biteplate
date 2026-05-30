<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\Money;

/**
 * Group Discount — a percentage off once the party reaches a threshold size
 * (default: 10% off for parties of 6 or more). Below the threshold it behaves
 * exactly like standard pricing.
 */
final class GroupDiscountPricing implements PricingStrategy
{
    public function __construct(
        private readonly int $minPartySize = 6,
        private readonly float $percent = 10.0,
    ) {
    }

    public function name(): string
    {
        return sprintf('Group Discount (%d+)', $this->minPartySize);
    }

    public function calculate(PricingContext $context): PricingResult
    {
        $subtotal = $context->subtotal();

        if ($context->partySize < $this->minPartySize) {
            return new PricingResult(
                $this->name(),
                $subtotal,
                Money::zero(),
                [sprintf('Party of %d — minimum %d for group discount', $context->partySize, $this->minPartySize)],
            );
        }

        return new PricingResult(
            $this->name(),
            $subtotal,
            $subtotal->percentage($this->percent),
            [sprintf('%g%% group discount for party of %d', $this->percent, $context->partySize)],
        );
    }
}
