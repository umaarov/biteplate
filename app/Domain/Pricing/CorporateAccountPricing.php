<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Corporate Account — a negotiated flat percentage off (default 15%), with the
 * balance invoiced to the company rather than settled at the table.
 */
final class CorporateAccountPricing implements PricingStrategy
{
    public function __construct(
        private readonly string $accountName,
        private readonly float $percent = 15.0,
    ) {
    }

    public function name(): string
    {
        return 'Corporate — '.$this->accountName;
    }

    public function calculate(PricingContext $context): PricingResult
    {
        $subtotal = $context->subtotal();

        return new PricingResult(
            $this->name(),
            $subtotal,
            $subtotal->percentage($this->percent),
            [
                sprintf('%g%% corporate rate (%s)', $this->percent, $this->accountName),
                'Balance invoiced to corporate account',
            ],
        );
    }
}
