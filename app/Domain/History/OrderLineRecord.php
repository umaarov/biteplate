<?php

declare(strict_types=1);

namespace App\Domain\History;

use App\Domain\Menu\MenuCategory;
use App\Domain\Shared\Money;

/** An immutable snapshot of one order line, frozen at the moment of confirmation. */
final readonly class OrderLineRecord
{
    public function __construct(
        public string $name,
        public int $quantity,
        public Money $lineTotal,
        public MenuCategory $category,
    ) {
    }

    public function isDrink(): bool
    {
        return $this->category->isDrink();
    }
}
