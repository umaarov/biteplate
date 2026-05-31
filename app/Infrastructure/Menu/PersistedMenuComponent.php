<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu;

use App\Domain\Menu\HasCategory;
use App\Domain\Menu\MenuCategory;
use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * A {@see MenuComponent} reconstructed from a saved order line.
 *
 * When an order is persisted, its decorator/composite tree is flattened into the
 * facts that actually matter downstream — the fully-loaded price, the merged
 * allergens, the prep notes, the target station and the category. On load we
 * rebuild a single component carrying those facts, so the rehydrated
 * {@see \App\Domain\Ordering\Order} behaves identically for kitchen routing,
 * billing and analytics without us serialising an arbitrary object graph.
 */
final class PersistedMenuComponent implements MenuComponent, HasCategory
{
    /**
     * @param list<Allergen>      $allergens
     * @param list<string>        $notes
     * @param list<KitchenTicket> $tickets  Full restored ticket set (a combo has many); empty falls back to a single ticket.
     */
    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly Money $price,
        private readonly array $allergens,
        private readonly KitchenStation $station,
        private readonly MenuCategory $category,
        private readonly array $notes = [],
        private readonly ?string $summaryText = null,
        private readonly array $tickets = [],
    ) {
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
        return $this->price;
    }

    public function category(): MenuCategory
    {
        return $this->category;
    }

    public function allergens(): array
    {
        return $this->allergens;
    }

    public function kitchenTickets(): array
    {
        if ($this->tickets !== []) {
            return $this->tickets;
        }

        return [new KitchenTicket($this->name, $this->station, $this->notes, $this->allergens)];
    }

    public function summary(): string
    {
        return $this->summaryText ?? sprintf('%s — %s', $this->name, $this->price->format());
    }
}
