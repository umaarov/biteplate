<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\Money;

/**
 * Immutable specification handed to a {@see MenuItemFactory}. Keeping the raw
 * data in one value object means the factory's signature never churns when a new
 * attribute is added, and the spec can be built straight from a database row or
 * a seasonal-menu JSON file.
 */
final readonly class MenuItemSpec
{
    /**
     * @param list<\App\Domain\Shared\Allergen> $allergens
     */
    public function __construct(
        public string $sku,
        public string $name,
        public string $description,
        public Money $price,
        public array $allergens = [],
        public ?KitchenStation $station = null,
    ) {
    }
}
