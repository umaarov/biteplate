<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * COMPOSITE — a set meal / combo deal that is itself a {@see MenuComponent}.
 *
 * A combo holds a list of child components (which may themselves be combos,
 * giving arbitrary nesting) and presents the exact same interface as a single
 * dish. The billing engine, the kitchen router and the receipt printer treat
 * "Burger Meal Deal" and "Side of Fries" through identical calls — they never
 * branch on whether they hold a leaf or a composite. That uniformity is the
 * whole point of the pattern.
 *
 * An optional bundle discount lets the deal cost less than its parts, which is
 * how real "meal deals" earn their name.
 */
final class ComboMeal implements MenuComponent, HasCategory
{
    /** @var list<MenuComponent> */
    private array $items = [];

    public function __construct(
        private readonly string $name,
        private readonly string $description = '',
        private readonly float $bundleDiscountPercent = 0.0,
    ) {
    }

    public function add(MenuComponent $component): self
    {
        $this->items[] = $component;

        return $this;
    }

    /** @return list<MenuComponent> */
    public function items(): array
    {
        return $this->items;
    }

    public function category(): MenuCategory
    {
        return MenuCategory::Combo;
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
        $total = Money::zero();

        foreach ($this->items as $item) {
            $total = $total->add($item->price());
        }

        if ($this->bundleDiscountPercent > 0.0) {
            $total = $total->subtract($total->percentage($this->bundleDiscountPercent));
        }

        return $total;
    }

    /** @return list<Allergen> */
    public function allergens(): array
    {
        $all = [];

        foreach ($this->items as $item) {
            foreach ($item->allergens() as $allergen) {
                $all[$allergen->value] = $allergen;
            }
        }

        return array_values($all);
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        $tickets = [];

        foreach ($this->items as $item) {
            foreach ($item->kitchenTickets() as $ticket) {
                $tickets[] = $ticket;
            }
        }

        return $tickets;
    }

    public function summary(): string
    {
        $lines = [sprintf('%s (combo) — %s', $this->name, $this->price()->format())];

        foreach ($this->items as $item) {
            foreach (explode("\n", $item->summary()) as $line) {
                $lines[] = '   '.$line;
            }
        }

        return implode("\n", $lines);
    }
}
