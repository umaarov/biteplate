<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;

/** Turns a tip percentage (or none) into a money amount on a given base. */
final class TipCalculator
{
    public function percentageOf(Money $base, float $percent): Money
    {
        if ($percent < 0) {
            throw new DomainException('Tip percentage cannot be negative.');
        }

        return $base->percentage($percent);
    }
}
