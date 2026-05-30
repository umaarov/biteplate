<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Pricing\CorporateAccountPricing;
use App\Domain\Pricing\GroupDiscountPricing;
use App\Domain\Pricing\HappyHourPricing;
use App\Domain\Pricing\LoyaltyCardPricing;
use App\Domain\Pricing\PricingStrategy;
use App\Domain\Pricing\StandardPricing;
use App\Domain\Pricing\TimeOfDayPricingResolver;
use DateTimeImmutable;

/**
 * Resolves a UI-chosen pricing key into a concrete {@see PricingStrategy}. The
 * "auto" key defers to the {@see TimeOfDayPricingResolver} (Scenario B), so the
 * cashier can either pin a strategy or let the clock decide.
 */
final class PricingStrategyRegistry
{
    public function __construct(private readonly TimeOfDayPricingResolver $resolver = new TimeOfDayPricingResolver())
    {
    }

    /** @return array<string, string> key => label, for select inputs. */
    public function options(): array
    {
        return [
            'auto' => 'Automatic (time of day)',
            'standard' => 'Standard',
            'happy_hour' => 'Happy Hour (20% off)',
            'loyalty' => 'Loyalty Card (10% + free drink)',
            'group' => 'Group Discount (6+)',
            'corporate' => 'Corporate Account (15%)',
        ];
    }

    public function make(string $key, ?DateTimeImmutable $at = null): PricingStrategy
    {
        return match ($key) {
            'happy_hour' => new HappyHourPricing(20.0),
            'loyalty' => new LoyaltyCardPricing(),
            'group' => new GroupDiscountPricing(),
            'corporate' => new CorporateAccountPricing('BitePlate Corporate'),
            'auto' => $this->resolver->resolveFor($at ?? new DateTimeImmutable()),
            default => new StandardPricing(),
        };
    }
}
