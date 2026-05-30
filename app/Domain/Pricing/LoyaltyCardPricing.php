<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Loyalty Card — 10% off the bill plus the customer's cheapest soft/soft-style
 * drink for free. Demonstrates that a strategy can combine several rules and
 * still expose a single, uniform result to the billing engine.
 */
final class LoyaltyCardPricing implements PricingStrategy
{
    public function __construct(private readonly float $percent = 10.0)
    {
    }

    public function name(): string
    {
        return 'Loyalty Card';
    }

    public function calculate(PricingContext $context): PricingResult
    {
        $subtotal = $context->subtotal();
        $discount = $subtotal->percentage($this->percent);
        $notes = [sprintf('%g%% loyalty discount applied', $this->percent)];

        $freeDrink = $context->cheapestDrink();

        if ($freeDrink !== null) {
            $discount = $discount->add($freeDrink->price);
            $notes[] = sprintf('Free drink: %s (%s)', $freeDrink->name, $freeDrink->price->format());
        }

        return new PricingResult($this->name(), $subtotal, $discount, $notes);
    }
}
