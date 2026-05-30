<?php

declare(strict_types=1);

namespace App\Domain\Menu;

use App\Domain\Shared\Allergen;
use App\Domain\Shared\KitchenTicket;
use App\Domain\Shared\Money;

/**
 * The single abstraction every "orderable thing" implements.
 *
 * This interface is the linchpin that lets three patterns coexist on one type:
 *
 *  - COMPOSITE  — a {@see ComboMeal} is a MenuComponent that contains other
 *                 MenuComponents, so a combo and a single dish are priced,
 *                 displayed and routed to the kitchen through identical calls.
 *  - DECORATOR  — a {@see Customization\MenuItemDecorator} is a MenuComponent
 *                 that wraps another MenuComponent, layering on price and prep
 *                 notes without the caller knowing it is decorated.
 *  - FACTORY    — concrete leaves ({@see MenuItem} subclasses) are produced by
 *                 {@see Factory\MenuItemFactory} / {@see Factory\MenuFactory}.
 *
 * Because the rest of the system codes against this interface and never against
 * the concretes, it exhibits the polymorphism that makes those patterns work.
 */
interface MenuComponent
{
    public function name(): string;

    public function description(): string;

    /** Fully-loaded price: base price plus every decoration and child. */
    public function price(): Money;

    /** @return list<Allergen> Distinct allergens present after customisation. */
    public function allergens(): array;

    /** @return list<KitchenTicket> One ticket per physical dish the kitchen must make. */
    public function kitchenTickets(): array;

    /** Indented, human-readable breakdown for receipts and kitchen dockets. */
    public function summary(): string;
}
