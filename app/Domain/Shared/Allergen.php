<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * The fourteen UK FSA major allergens, trimmed to the set BitePlate tracks.
 *
 * Modelled as a backed enum so allergen handling is type-safe end to end —
 * you cannot accidentally tag a dish with the string "nutz" and slip it past
 * the allergy alert system.
 */
enum Allergen: string
{
    case Gluten = 'gluten';
    case Dairy = 'dairy';
    case Nuts = 'nuts';
    case Peanuts = 'peanuts';
    case Shellfish = 'shellfish';
    case Fish = 'fish';
    case Soy = 'soy';
    case Egg = 'egg';
    case Sesame = 'sesame';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Allergens BitePlate treats as life-threatening, triggering a manager alert. */
    public function isHighRisk(): bool
    {
        return in_array($this, [self::Nuts, self::Peanuts, self::Shellfish], true);
    }
}
