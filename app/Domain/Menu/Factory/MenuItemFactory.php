<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Menu\MenuItem;
use App\Domain\Shared\DomainException;

/**
 * FACTORY METHOD — defines the skeleton of item creation while deferring the
 * actual instantiation to subclasses.
 *
 * {@see create()} is a template method holding the validation rules common to
 * every menu item; the abstract {@see make()} is the "factory method" each
 * concrete factory overrides to return its specific {@see MenuItem} subtype.
 * Callers ask a factory for an item and receive a fully-built object without
 * naming a concrete class — so adding, say, a "SeasonalSpecialFactory" later
 * touches no existing code.
 */
abstract class MenuItemFactory
{
    final public function create(MenuItemSpec $spec): MenuItem
    {
        if (trim($spec->name) === '') {
            throw new DomainException('A menu item must have a name.');
        }

        if ($spec->price->isNegative()) {
            throw new DomainException("Menu item '{$spec->name}' cannot have a negative price.");
        }

        return $this->make($spec);
    }

    /** The factory method proper — each subclass returns its product type. */
    abstract protected function make(MenuItemSpec $spec): MenuItem;
}
