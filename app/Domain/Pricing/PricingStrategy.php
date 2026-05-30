<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * STRATEGY — an interchangeable pricing algorithm.
 *
 * The billing engine holds a reference to a PricingStrategy and delegates the
 * "what does this cost?" decision to it. Swapping Happy Hour for Loyalty Card
 * is a one-line change of the held strategy object, with no edit to the billing
 * code — the open/closed principle in action. New strategies (corporate,
 * student, voucher) plug in simply by implementing this interface.
 */
interface PricingStrategy
{
    public function name(): string;

    public function calculate(PricingContext $context): PricingResult;
}
