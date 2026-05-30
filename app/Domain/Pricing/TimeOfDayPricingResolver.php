<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use DateTimeImmutable;

/**
 * Scenario B — automatic, time-driven selection of a {@see PricingStrategy}.
 *
 * The rules are expressed as an ordered list of (predicate → strategy) pairs.
 * Adding a new pricing window ("Student Tuesdays", a bank-holiday rate) means
 * adding one row to {@see rules()} — the resolver logic and every strategy stay
 * untouched. This keeps Strategy selection itself open for extension.
 */
final class TimeOfDayPricingResolver
{
    public function resolveFor(DateTimeImmutable $moment): PricingStrategy
    {
        foreach ($this->rules() as [$matches, $strategy]) {
            if ($matches($moment)) {
                return $strategy;
            }
        }

        return new StandardPricing();
    }

    /**
     * @return list<array{0: callable(DateTimeImmutable): bool, 1: PricingStrategy}>
     */
    private function rules(): array
    {
        return [
            // Saturday evening (18:00–23:00): weekend surcharge.
            [
                static function (DateTimeImmutable $m): bool {
                    $hour = (int) $m->format('G');

                    return (int) $m->format('N') === 6 && $hour >= 18 && $hour < 23;
                },
                new WeekendSurchargePricing(),
            ],
            // Quiet Hours (15:00–17:00) any day: 20% off.
            [
                static function (DateTimeImmutable $m): bool {
                    $hour = (int) $m->format('G');

                    return $hour >= 15 && $hour < 17;
                },
                new HappyHourPricing(20.0),
            ],
        ];
    }
}
