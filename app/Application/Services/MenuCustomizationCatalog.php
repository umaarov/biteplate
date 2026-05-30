<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Shared\Allergen;

/**
 * The small, curated set of customisations the POS offers per dish. Each extra
 * maps to an {@see \App\Domain\Menu\Customization\ExtraAddOn} decorator; allergen
 * options map to {@see \App\Domain\Menu\Customization\AllergenRequirement}.
 */
final class MenuCustomizationCatalog
{
    /** @return list<array{key: string, label: string, surcharge_minor: int, allergen: ?string}> */
    public function extras(): array
    {
        return [
            ['key' => 'cheese', 'label' => 'Extra Cheese', 'surcharge_minor' => 120, 'allergen' => Allergen::Dairy->value],
            ['key' => 'bacon', 'label' => 'Smoked Bacon', 'surcharge_minor' => 200, 'allergen' => null],
            ['key' => 'egg', 'label' => 'Fried Egg', 'surcharge_minor' => 150, 'allergen' => Allergen::Egg->value],
            ['key' => 'avocado', 'label' => 'Avocado', 'surcharge_minor' => 180, 'allergen' => null],
            ['key' => 'fries', 'label' => 'Side of Fries', 'surcharge_minor' => 350, 'allergen' => null],
        ];
    }

    /** @return array{key: string, label: string, surcharge_minor: int, allergen: ?string}|null */
    public function findExtra(string $key): ?array
    {
        foreach ($this->extras() as $extra) {
            if ($extra['key'] === $key) {
                return $extra;
            }
        }

        return null;
    }

    /** @return list<Allergen> */
    public function allergenOptions(): array
    {
        return Allergen::cases();
    }
}
