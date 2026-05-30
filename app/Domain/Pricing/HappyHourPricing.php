<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Happy Hour / Quiet Hours — a flat percentage off the whole bill (default 20%).
 * Used directly, and also as the strategy the {@see TimeOfDayPricingResolver}
 * selects automatically between 3pm and 5pm (Scenario B).
 */
final class HappyHourPricing implements PricingStrategy
{
    public function __construct(private readonly float $percent = 20.0)
    {
    }

    public function name(): string
    {
        return sprintf('Happy Hour (%g%% off)', $this->percent);
    }

    public function calculate(PricingContext $context): PricingResult
    {
        $subtotal = $context->subtotal();
        $discount = $subtotal->percentage($this->percent);

        return new PricingResult(
            $this->name(),
            $subtotal,
            $discount,
            [sprintf('%g%% Happy Hour discount applied', $this->percent)],
        );
    }
}
