<?php

declare(strict_types=1);

namespace App\Domain\Menu;

/**
 * Implemented by any {@see MenuComponent} that can report its category. Lets
 * billing and analytics classify a line (food vs. drink) without caring whether
 * it is a plain dish, a decorated dish, a combo, or one rebuilt from the database.
 */
interface HasCategory
{
    public function category(): MenuCategory;
}
