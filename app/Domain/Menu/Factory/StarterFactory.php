<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Menu\MenuItem;
use App\Domain\Menu\Starter;

final class StarterFactory extends MenuItemFactory
{
    protected function make(MenuItemSpec $spec): MenuItem
    {
        return new Starter($spec->sku, $spec->name, $spec->description, $spec->price, $spec->allergens, $spec->station);
    }
}
