<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Shared\Money;

/** The default strategy: no discount, no surcharge. */
final class StandardPricing implements PricingStrategy
{
    public function name(): string
    {
        return 'Standard';
    }

    public function calculate(PricingContext $context): PricingResult
    {
        return new PricingResult($this->name(), $context->subtotal(), Money::zero());
    }
}
