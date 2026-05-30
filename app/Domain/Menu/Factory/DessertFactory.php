<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Menu\Dessert;
use App\Domain\Menu\MenuItem;

final class DessertFactory extends MenuItemFactory
{
    protected function make(MenuItemSpec $spec): MenuItem
    {
        return new Dessert($spec->sku, $spec->name, $spec->description, $spec->price, $spec->allergens, $spec->station);
    }
}
