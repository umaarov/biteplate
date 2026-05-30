<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;

/**
 * Splits a total between guests to the penny. Any indivisible remainder is
 * distributed one minor unit at a time to the earliest guests, so the parts
 * always sum back to the exact total — never £0.01 over or under.
 */
final class SplitBillCalculator
{
    /**
     * @return list<Money>
     */
    public function split(Money $total, int $ways): array
    {
        if ($ways < 1) {
            throw new DomainException('A bill must be split between at least one guest.');
        }

        $base = intdiv($total->minorUnits, $ways);
        $remainder = $total->minorUnits % $ways;

        $shares = [];
        for ($i = 0; $i < $ways; $i++) {
            $minor = $base + ($i < $remainder ? 1 : 0);
            $shares[] = Money::fromMinor($minor, $total->currency);
        }

        return $shares;
    }
}
