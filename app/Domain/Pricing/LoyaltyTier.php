<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

enum LoyaltyTier: string
{
    case None = 'none';
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';

    public function label(): string
    {
        return $this === self::None ? 'No card' : ucfirst($this->value).' member';
    }
}
