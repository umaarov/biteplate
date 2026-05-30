<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

/** The default high-street BitePlate branch — core menu, no local additions. */
final class StandardMenuFactory extends AbstractMenuFactory
{
    public function locationName(): string
    {
        return 'BitePlate High Street';
    }
}
