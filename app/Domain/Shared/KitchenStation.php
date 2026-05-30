<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Physical preparation stations in a BitePlate kitchen.
 *
 * Drives the multi-screen routing in {@see \App\Domain\Kitchen\StationRouter}
 * (Scenario E): a ticket carries the station it must appear on.
 */
enum KitchenStation: string
{
    case Hot = 'hot';
    case Cold = 'cold';
    case Dessert = 'dessert';
    case Bar = 'bar';
    case Pass = 'pass';

    public function label(): string
    {
        return match ($this) {
            self::Hot => 'Hot Station',
            self::Cold => 'Cold Station',
            self::Dessert => 'Dessert Station',
            self::Bar => 'Bar',
            self::Pass => 'Pass',
        };
    }
}
