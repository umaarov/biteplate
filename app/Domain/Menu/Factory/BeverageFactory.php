<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Menu\Beverage;
use App\Domain\Menu\MenuItem;

final class BeverageFactory extends MenuItemFactory
{
    protected function make(MenuItemSpec $spec): MenuItem
    {
        return new Beverage($spec->sku, $spec->name, $spec->description, $spec->price, $spec->allergens, $spec->station);
    }
}
