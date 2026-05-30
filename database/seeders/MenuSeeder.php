<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Menu\Factory\CityCentreMenuFactory;
use App\Domain\Menu\Factory\CoastalMenuFactory;
use App\Domain\Menu\Factory\MenuFactory;
use App\Domain\Menu\Factory\StandardMenuFactory;
use App\Domain\Menu\MenuItem as DomainMenuItem;
use App\Domain\Menu\Side;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\Money;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Builds the menu through the configured franchise Abstract Factory (Scenario C)
 * and persists each produced item. The factory decides which dishes exist; the
 * seeder just stores them — no menu data is hard-coded here.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $factory = $this->factoryForBranch(config('biteplate.branch'));

        foreach ($factory->fullMenu() as $item) {
            $this->persist($item);
        }

        // A couple of à la carte sides for combos and Build-Your-Own-Burger.
        $this->persist(new Side('SD-FRIES', 'Triple-Cooked Chips', 'Hand-cut, twice fried', Money::of(3.95), [], KitchenStation::Hot));
        $this->persist(new Side('SD-SLAW', 'House Slaw', 'Crunchy, lightly dressed', Money::of(2.95), [Allergen::Egg], KitchenStation::Cold));
    }

    private function factoryForBranch(string $branch): MenuFactory
    {
        return match ($branch) {
            'coastal' => new CoastalMenuFactory(),
            'city_centre' => new CityCentreMenuFactory(),
            default => new StandardMenuFactory(),
        };
    }

    private function persist(DomainMenuItem $item): void
    {
        MenuItem::updateOrCreate(
            ['sku' => $item->sku()],
            [
                'name' => $item->name(),
                'description' => $item->description(),
                'category' => $item->category()->value,
                'station' => $item->station()->value,
                'price_minor' => $item->price()->minorUnits,
                'currency' => $item->price()->currency,
                'allergens' => array_map(static fn (Allergen $a) => $a->value, $item->allergens()),
                'branch' => null,
                'active' => true,
            ],
        );
    }
}
