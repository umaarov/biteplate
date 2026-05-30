<?php

declare(strict_types=1);

namespace App\Domain\Menu\Customization;

use App\Domain\Menu\MenuComponent;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * Swaps one component of a dish for another ("fries → side salad"), applying a
 * price delta that may be positive, zero or negative.
 */
final class SideSubstitution extends MenuItemDecorator
{
    public function __construct(
        MenuComponent $inner,
        private readonly string $from,
        private readonly string $to,
        private readonly Money $priceDelta,
    ) {
        parent::__construct($inner);
    }

    public function price(): Money
    {
        return $this->inner->price()->add($this->priceDelta);
    }

    /** @return list<KitchenTicket> */
    public function kitchenTickets(): array
    {
        return $this->annotateTickets(sprintf('Sub: %s → %s', $this->from, $this->to));
    }

    public function summary(): string
    {
        $delta = $this->priceDelta->isZero() ? 'no charge' : $this->priceDelta->format();

        return $this->inner->summary().sprintf("\n   ↔ %s instead of %s (%s)", $this->to, $this->from, $delta);
    }
}
