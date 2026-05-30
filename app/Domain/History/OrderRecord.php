<?php

declare(strict_types=1);

namespace App\Domain\History;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * The immutable audit entry written to the {@see OrderHistoryLog} when an order
 * is confirmed. Captures everything the reporting and analytics features need —
 * who, what, when, how much, under which pricing — so the log is the single
 * source of truth for the manager's dashboards (Scenario C & D).
 */
final readonly class OrderRecord
{
    /**
     * @param list<OrderLineRecord> $lines
     */
    public function __construct(
        public string $orderId,
        public int $tableNumber,
        public string $staffId,
        public array $lines,
        public Money $total,
        public DateTimeImmutable $placedAt,
        public string $pricingStrategy = 'Standard',
        public int $covers = 1,
        public bool $cancelled = false,
        public bool $wasteful = false,
    ) {
    }

    /** @return list<string> */
    public function itemNames(): array
    {
        $names = [];

        foreach ($this->lines as $line) {
            for ($i = 0; $i < $line->quantity; $i++) {
                $names[] = $line->name;
            }
        }

        return $names;
    }

    public function foodRevenue(): Money
    {
        return $this->revenueWhere(static fn (OrderLineRecord $l) => ! $l->isDrink());
    }

    public function drinkRevenue(): Money
    {
        return $this->revenueWhere(static fn (OrderLineRecord $l) => $l->isDrink());
    }

    /** @param callable(OrderLineRecord): bool $predicate */
    private function revenueWhere(callable $predicate): Money
    {
        $total = Money::zero($this->total->currency);

        foreach ($this->lines as $line) {
            if ($predicate($line)) {
                $total = $total->add($line->lineTotal);
            }
        }

        return $total;
    }
}
