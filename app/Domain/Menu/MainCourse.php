<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\KitchenStation;

final class MainCourse extends MenuItem
{
    public function category(): MenuCategory
    {
        return MenuCategory::Main;
    }

    protected function defaultStation(): KitchenStation
    {
        return KitchenStation::Hot;
    }
}
