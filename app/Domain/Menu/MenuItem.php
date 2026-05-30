<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * Base class for every concrete dish — the COMPOSITE pattern's "leaf".
 *
 * Holds the data and behaviour common to all menu items (encapsulated behind
 * readonly properties) and leaves two decisions to subclasses via abstract
 * methods: which {@see MenuCategory} the item belongs to and which
 * {@see KitchenStation} prepares it. Subclasses therefore differ only where it
 * matters, demonstrating inheritance without duplication.
 */
abstract class MenuItem implements MenuComponent, HasCategory
{
    /**
     * @param list<\App\Domain\Shared\Allergen> $allergens
     */
    public function __construct(
        protected readonly string $sku,
        protected readonly string $name,
        protected readonly string $description,
        protected readonly Money $basePrice,
        protected readonly array $allergens = [],
        protected readonly ?KitchenStation $stationOverride = null,
    ) {
    }

    abstract public function category(): MenuCategory;

    /** The station this kind of dish defaults to when none is specified. */
    abstract protected function defaultStation(): KitchenStation;

    public function station(): KitchenStation
    {
        return $this->stationOverride ?? $this->defaultStation();
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function price(): Money
    {
        return $this->basePrice;
    }

    /** @return list<\App\Domain\Shared\Allergen> */
    public function allergens(): array
    {
        return $this->allergens;
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return [new KitchenTicket($this->name, $this->station(), [], $this->allergens)];
    }

    public function summary(): string
    {
        return sprintf('%s — %s', $this->name, $this->price()->format());
    }
}
