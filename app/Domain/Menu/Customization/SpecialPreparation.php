<?php

declare(strict_types=1);

namespace App\Domain\Menu\Customization;

use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * A free-text preparation instruction ("well done", "extra crispy", "sauce on
 * the side"), optionally carrying a surcharge for premium handling.
 */
final class SpecialPreparation extends MenuItemDecorator
{
    public function __construct(
        MenuComponent $inner,
        private readonly string $instruction,
        private readonly ?Money $surcharge = null,
    ) {
        parent::__construct($inner);
    }

    public function price(): Money
    {
        return $this->surcharge === null
            ? $this->inner->price()
            : $this->inner->price()->add($this->surcharge);
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return $this->annotateTickets('» '.$this->instruction);
    }

    public function summary(): string
    {
        return $this->inner->summary()."\n   » ".$this->instruction;
    }
}
