<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Contracts\MenuRepository;
use App\Domain\Menu\Factory\BeverageFactory;
use App\Domain\Menu\Factory\DessertFactory;
use App\Domain\Menu\Factory\MainCourseFactory;
use App\Domain\Menu\Factory\MenuItemSpec;
use App\Domain\Menu\Factory\StarterFactory;
use App\Domain\Menu\MenuCategory;
use App\Domain\Menu\MenuItem;
use App\Domain\Menu\Side;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\Money;
use App\Models\MenuItem as MenuItemModel;

final class EloquentMenuRepository implements MenuRepository
{
    public function active(?string $branch = null): array
    {
        return MenuItemModel::query()
            ->where('active', true)
            ->when($branch !== null, fn ($q) => $q->where(fn ($q) => $q->whereNull('branch')->orWhere('branch', $branch)))
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function findModel(string $sku): ?MenuItemModel
    {
        return MenuItemModel::where('sku', $sku)->first();
    }

    public function leaf(string $sku): MenuItem
    {
        $model = MenuItemModel::where('sku', $sku)->firstOrFail();

        return $this->toLeaf($model);
    }

    public function toLeaf(MenuItemModel $model): MenuItem
    {
        $spec = new MenuItemSpec(
            sku: $model->sku,
            name: $model->name,
            description: (string) $model->description,
            price: Money::fromMinor($model->price_minor, $model->currency),
            allergens: array_map(static fn (string $a) => Allergen::from($a), $model->allergens ?? []),
            station: KitchenStation::from($model->station),
        );

        return match (MenuCategory::from($model->category)) {
            MenuCategory::Starter => (new StarterFactory())->create($spec),
            MenuCategory::Main => (new MainCourseFactory())->create($spec),
            MenuCategory::Dessert => (new DessertFactory())->create($spec),
            MenuCategory::Beverage => (new BeverageFactory())->create($spec),
            MenuCategory::Side, MenuCategory::Combo => new Side(
                $spec->sku, $spec->name, $spec->description, $spec->price, $spec->allergens, $spec->station
            ),
        };
    }
}
