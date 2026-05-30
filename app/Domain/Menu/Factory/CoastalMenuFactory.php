<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Shared\Allergen;
use App\Domain\Shared\Money;

/**
 * The coastal branch keeps the full core menu and appends a fresh seafood
 * section — added purely by overriding {@see mains()} and calling the parent.
 */
final class CoastalMenuFactory extends AbstractMenuFactory
{
    public function locationName(): string
    {
        return 'BitePlate Harbourside';
    }

    public function mains(): array
    {
        return [
            ...parent::mains(),
            $this->mains->create(new MenuItemSpec('MN-FISH', 'Beer-Battered Haddock', 'Line-caught haddock, triple-cooked chips, mushy peas', Money::of(16.50), [Allergen::Gluten, Allergen::Fish])),
            $this->mains->create(new MenuItemSpec('MN-MUSS', 'Moules Marinière', 'Rope-grown mussels, white wine, cream', Money::of(15.95), [Allergen::Shellfish, Allergen::Dairy])),
        ];
    }
}
