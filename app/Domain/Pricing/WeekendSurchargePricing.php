<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Weekend Surcharge (Scenario B) — adds a percentage to the bill on busy
 * Saturday evenings. Implemented as a strategy with a NEGATIVE discount, so the
 * billing engine needs no special case: a surcharge is simply "negative savings".
 */
final class WeekendSurchargePricing implements PricingStrategy
{
    public function __construct(private readonly float $percent = 10.0)
    {
    }

    public function name(): string
    {
        return sprintf('Weekend Surcharge (+%g%%)', $this->percent);
    }

    public function calculate(PricingContext $context): PricingResult
    {
        $subtotal = $context->subtotal();
        $surcharge = $subtotal->percentage($this->percent);

        return new PricingResult(
            $this->name(),
            $subtotal,
            $surcharge->multiply(-1), // negative discount == surcharge
            [sprintf('%g%% weekend surcharge applied', $this->percent)],
        );
    }
}
