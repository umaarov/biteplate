<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\KitchenStation;

final class Beverage extends MenuItem
{
    public function category(): MenuCategory
    {
        return MenuCategory::Beverage;
    }

    protected function defaultStation(): KitchenStation
    {
        return KitchenStation::Bar;
    }
}
