<?php

declare(strict_types=1);

namespace App\Domain\Menu;

enum MenuCategory: string
{
    case Starter = 'starter';
    case Main = 'main';
    case Dessert = 'dessert';
    case Beverage = 'beverage';
    case Side = 'side';
    case Combo = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Main Course',
            default => ucfirst($this->value),
        };
    }

    /** Used by the end-of-night report to split revenue food vs. drinks (Scenario D). */
    public function isDrink(): bool
    {
        return $this === self::Beverage;
    }
}
