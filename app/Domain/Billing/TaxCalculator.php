<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Shared\Money;

/** Computes tax (UK VAT by default) on a taxable amount. */
final class TaxCalculator
{
    public function __construct(private readonly float $ratePercent = 20.0)
    {
    }

    public function ratePercent(): float
    {
        return $this->ratePercent;
    }

    public function on(Money $taxable): Money
    {
        return $taxable->percentage($this->ratePercent);
    }
}
