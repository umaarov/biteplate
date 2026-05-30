<?php

declare(strict_types=1);

namespace App\Domain\Menu\Customization;

use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenTicket;

/**
 * A customer allergen requirement ("no nuts", "gluten-free please").
 *
 * Carries no surcharge but stamps a prominent, upper-cased instruction onto
 * every kitchen ticket. It deliberately does NOT strip the allergen from the
 * dish's declared allergens — cross-contamination risk means the allergy alert
 * system must still treat the dish as containing it until the kitchen confirms.
 */
final class AllergenRequirement extends MenuItemDecorator
{
    public function __construct(
        MenuComponent $inner,
        private readonly Allergen $avoid,
    ) {
        parent::__construct($inner);
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return $this->annotateTickets('⚠ NO '.strtoupper($this->avoid->value));
    }

    public function summary(): string
    {
        return $this->inner->summary()."\n   ⚠ Allergen request: no ".$this->avoid->value;
    }
}
