<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

/**
 * ABSTRACT FACTORY — produces a whole family of related menu items for one
 * BitePlate location (Scenario C: the Franchise Rollout).
 *
 * Head office ships the {@see AbstractMenuFactory} core; each franchise provides
 * a concrete factory (Coastal, City-Centre, …) that yields its own variation of
 * the menu family. The core system asks a MenuFactory for "the menu" and never
 * needs to know which branch it is running in — so franchise owners get
 * location-specific menus without ever touching shared code.
 *
 * @return list<\App\Domain\Menu\MenuItem>
 */
interface MenuFactory
{
    public function locationName(): string;

    /** @return list<\App\Domain\Menu\MenuItem> */
    public function starters(): array;

    /** @return list<\App\Domain\Menu\MenuItem> */
    public function mains(): array;

    /** @return list<\App\Domain\Menu\MenuItem> */
    public function desserts(): array;

    /** @return list<\App\Domain\Menu\MenuItem> */
    public function beverages(): array;

    /** Convenience: the entire menu flattened, in serving order. */
    public function fullMenu(): array;
}
