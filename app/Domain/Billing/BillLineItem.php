<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Shared\Money;

/**
 * A single printed line on a bill. It is COMPOSED into a {@see Bill}: it carries
 * no identity of its own and has no meaning outside the bill that owns it — when
 * the bill is discarded, its line items go with it. That whole-part lifetime
 * dependency is the textbook composition relationship.
 */
final readonly class BillLineItem
{
    public function __construct(
        public string $description,
        public int $quantity,
        public Money $amount,
    ) {
    }

    public function label(): string
    {
        return $this->quantity > 1
            ? sprintf('%d × %s', $this->quantity, $this->description)
            : $this->description;
    }
}
