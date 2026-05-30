<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\Money;

/**
 * The city-centre branch adds a fast "grab & go" starter range for the lunch
 * rush, prepared at the cold station so the hot line stays free for mains.
 */
final class CityCentreMenuFactory extends AbstractMenuFactory
{
    public function locationName(): string
    {
        return 'BitePlate City Exchange';
    }

    public function starters(): array
    {
        return [
            ...parent::starters(),
            $this->starters->create(new MenuItemSpec('GG-WRAP', 'Grab & Go Chicken Wrap', 'Tortilla wrap, chargrilled chicken, slaw', Money::of(5.95), [Allergen::Gluten], KitchenStation::Cold)),
            $this->starters->create(new MenuItemSpec('GG-SALD', 'Grab & Go Caesar Box', 'Cos lettuce, croutons, parmesan, dressing', Money::of(5.50), [Allergen::Gluten, Allergen::Dairy, Allergen::Fish], KitchenStation::Cold)),
        ];
    }
}
