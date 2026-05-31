<?php

declare(strict_types=1);

namespace App\Application\Services;

/**
 * The curated set of combo / set-meal deals the POS offers. Each entry is a
 * recipe of existing menu SKUs plus an optional bundle discount; the
 * {@see OrderService} assembles it into a {@see \App\Domain\Menu\ComboMeal}
 * (Composite) at order time, so a deal costs less than its parts yet routes
 * each child to its own kitchen station and prices/displays uniformly.
 */
final class ComboCatalog
{
    /** @return list<array{key: string, name: string, description: string, skus: list<string>, discount: float}> */
    public function all(): array
    {
        return [
            [
                'key' => 'burger-meal',
                'name' => 'Classic Burger Meal',
                'description' => 'Cheeseburger, triple-cooked chips & a Cola',
                'skus' => ['MN-BURG', 'SD-FRIES', 'BV-COLA'],
                'discount' => 15.0,
            ],
            [
                'key' => 'sharing-starters',
                'name' => 'Sharing Starter Platter',
                'description' => 'Buffalo wings, bruschetta & house slaw to share',
                'skus' => ['ST-WING', 'ST-BRUS', 'SD-SLAW'],
                'discount' => 10.0,
            ],
            [
                'key' => 'sweet-finish',
                'name' => 'Sweet Finish for Two',
                'description' => 'Baked cheesecake, chocolate brownie & two lemonades',
                'skus' => ['DS-CHEE', 'DS-BROWN', 'BV-LEMO', 'BV-LEMO'],
                'discount' => 12.5,
            ],
        ];
    }

    /** @return array{key: string, name: string, description: string, skus: list<string>, discount: float}|null */
    public function find(string $key): ?array
    {
        foreach ($this->all() as $combo) {
            if ($combo['key'] === $key) {
                return $combo;
            }
        }

        return null;
    }
}
