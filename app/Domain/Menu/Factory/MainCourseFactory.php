<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Menu\MainCourse;
use App\Domain\Menu\MenuItem;

final class MainCourseFactory extends MenuItemFactory
{
    protected function make(MenuItemSpec $spec): MenuItem
    {
        return new MainCourse($spec->sku, $spec->name, $spec->description, $spec->price, $spec->allergens, $spec->station);
    }
}
