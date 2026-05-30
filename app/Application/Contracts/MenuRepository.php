<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Menu\MenuItem;
use App\Models\MenuItem as MenuItemModel;

/**
 * Port for reading the menu. The application depends on this interface; the
 * Eloquent implementation lives in the infrastructure layer (Dependency
 * Inversion), so the use-cases never import Eloquent directly.
 */
interface MenuRepository
{
    /** @return list<MenuItemModel> Active items for display, optionally for one branch. */
    public function active(?string $branch = null): array;

    public function findModel(string $sku): ?MenuItemModel;

    /** Build the domain leaf component for a SKU (via the menu-item factories). */
    public function leaf(string $sku): MenuItem;
}
