<?php

declare(strict_types=1);

namespace App\Domain\Menu\Customization;

use App\Domain\Menu\HasCategory;
use App\Domain\Menu\MenuCategory;
use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * DECORATOR — base class for runtime customisations layered onto any
 * {@see MenuComponent}.
 *
 * Because a decorator both implements MenuComponent and wraps one, customisations
 * stack arbitrarily: a Cheeseburger can be wrapped in ExtraAddOn(bacon), then
 * SideSubstitution(fries → salad), then AllergenRequirement(no gluten) — and the
 * result is still "just a MenuComponent" to the order, the bill and the kitchen.
 * That is how 200+ burger permutations (Scenario A) are expressed with a handful
 * of classes instead of a subclass explosion.
 *
 * Each subclass overrides only the facets it changes; everything else delegates
 * to the wrapped component.
 */
abstract class MenuItemDecorator implements MenuComponent, HasCategory
{
    public function __construct(protected readonly MenuComponent $inner)
    {
    }

    public function category(): MenuCategory
    {
        return $this->inner instanceof HasCategory ? $this->inner->category() : MenuCategory::Main;
    }

    public function name(): string
    {
        return $this->inner->name();
    }

    public function description(): string
    {
        return $this->inner->description();
    }

    public function price(): Money
    {
        return $this->inner->price();
    }

    /** @return list<Allergen> */
    public function allergens(): array
    {
        return $this->inner->allergens();
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return $this->inner->kitchenTickets();
    }

    public function summary(): string
    {
        return $this->inner->summary();
    }

    /**
     * Helper for subclasses: stamp a prep note (and optional extra allergens)
     * onto every ticket produced by the wrapped component.
     *
     * @param  list<Allergen>  $extraAllergens
     * @return list<KitchenTicket>
     */
    protected function annotateTickets(string $note, array $extraAllergens = []): array
    {
        return array_map(
            fn (KitchenTicket $ticket): KitchenTicket => $ticket->withNote($note)->withAllergens($extraAllergens),
            $this->inner->kitchenTickets(),
        );
    }

    /** @param list<Allergen> $extra */
    protected function mergeAllergens(array $extra): array
    {
        $merged = [];

        foreach ([...$this->inner->allergens(), ...$extra] as $allergen) {
            $merged[$allergen->value] = $allergen;
        }

        return array_values($merged);
    }
}
