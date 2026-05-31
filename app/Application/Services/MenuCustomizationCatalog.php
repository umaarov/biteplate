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

    /**
     * Side / component swaps offered per dish. Each maps to a
     * {@see \App\Domain\Menu\Customization\SideSubstitution} decorator with a
     * price delta that may be positive, zero or negative.
     *
     * @return list<array{key: string, from: string, to: string, delta_minor: int}>
     */
    public function substitutions(): array
    {
        return [
            ['key' => 'salad', 'from' => 'Fries', 'to' => 'Side Salad', 'delta_minor' => 0],
            ['key' => 'sweet-fries', 'from' => 'Fries', 'to' => 'Sweet Potato Fries', 'delta_minor' => 100],
            ['key' => 'gf-bun', 'from' => 'Bun', 'to' => 'Gluten-free Bun', 'delta_minor' => 80],
        ];
    }

    /** @return array{key: string, from: string, to: string, delta_minor: int}|null */
    public function findSubstitution(string $key): ?array
    {
        foreach ($this->substitutions() as $sub) {
            if ($sub['key'] === $key) {
                return $sub;
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
