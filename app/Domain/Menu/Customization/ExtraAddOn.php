<?php

declare(strict_types=1);

namespace App\Domain\Menu\Customization;

use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * Adds an extra (bacon, extra cheese, an additional shot) for a surcharge,
 * optionally introducing a new allergen the kitchen and allergy alert system
 * must know about.
 */
final class ExtraAddOn extends MenuItemDecorator
{
    public function __construct(
        MenuComponent $inner,
        private readonly string $label,
        private readonly Money $surcharge,
        private readonly ?Allergen $addedAllergen = null,
    ) {
        parent::__construct($inner);
    }

    public function price(): Money
    {
        return $this->inner->price()->add($this->surcharge);
    }

    /** @return list<Allergen> */
    public function allergens(): array
    {
        return $this->addedAllergen === null
            ? $this->inner->allergens()
            : $this->mergeAllergens([$this->addedAllergen]);
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return $this->annotateTickets(
            '+ '.$this->label,
            $this->addedAllergen === null ? [] : [$this->addedAllergen],
        );
    }

    public function summary(): string
    {
        return $this->inner->summary()."\n   + ".$this->label.' ('.$this->surcharge->format().')';
    }
}
