<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\KitchenStation;

final class Starter extends MenuItem
{
    public function category(): MenuCategory
    {
        return MenuCategory::Starter;
    }

    protected function defaultStation(): KitchenStation
    {
        return KitchenStation::Cold;
    }
}
