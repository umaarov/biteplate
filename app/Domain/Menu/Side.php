<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\KitchenStation;

/**
 * A side dish. Exists primarily as a building block for combo meals and the
 * "Build Your Own Burger" feature (Scenario A), where it is composed onto a
 * base via {@see Customization\ExtraAddOn} decorators.
 */
final class Side extends MenuItem
{
    public function category(): MenuCategory
    {
        return MenuCategory::Side;
    }

    protected function defaultStation(): KitchenStation
    {
        return KitchenStation::Hot;
    }
}
