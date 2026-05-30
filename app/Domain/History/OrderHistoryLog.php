<?php

declare(strict_types=1);

namespace App\Domain\History;

use DateTimeImmutable;
use IteratorAggregate;

/**
 * SINGLETON — the one, globally-reachable order history / audit log.
 *
 * Every confirmed order is appended here exactly once, and every subsystem
 * (billing, analytics, the end-of-night report) reads from this single instance,
 * guaranteeing one authoritative record set. Construction is locked down with a
 * private constructor; {@see instance()} is the only way in.
 *
 * It is also an {@see IteratorAggregate}, so callers traverse it with `foreach`
 * via the {@see OrderHistoryIterator} without knowing how records are stored.
 *
 * NOTE on the trade-off: a process-wide static is convenient but is shared
 * mutable state — under a long-lived runtime (Octane/FrankenPHP) it survives
 * between requests, and it complicates unit testing. {@see reset()} exists so the
 * application layer can give each request a clean, isolated log, and so tests can
 * start from zero. This is discussed at length in EVALUATION.md.
 *
 * @implements IteratorAggregate<int, OrderRecord>
 */
final class OrderHistoryLog implements IteratorAggregate
{
    private static ?self $instance = null;

    /** @var list<OrderRecord> */
    private array $records = [];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** Drop the singleton (per-request isolation under Octane, and test setup). */
    public static function reset(): void
    {
        self::$instance = null;
    }

    public function append(OrderRecord $record): void
    {
        $this->records[] = $record;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function getIterator(): OrderHistoryIterator
    {
        return new OrderHistoryIterator($this->records);
    }

    /**
     * Orders placed within an inclusive date range.
     *
     * @return list<OrderRecord>
     */
    public function inDateRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $out = [];

        foreach ($this as $record) {
            if ($record->placedAt >= $from && $record->placedAt <= $to) {
                $out[] = $record;
            }
        }

        return $out;
    }

    /** @return list<OrderRecord> */
    public function forTable(int $tableNumber): array
    {
        $out = [];

        foreach ($this as $record) {
            if ($record->tableNumber === $tableNumber) {
                $out[] = $record;
            }
        }

        return $out;
    }

    /**
     * The single most frequently ordered item across the whole log.
     *
     * @return array{name: string, count: int}|null
     */
    public function mostFrequentItem(): ?array
    {
        $tally = $this->itemFrequency();

        if ($tally === []) {
            return null;
        }

        $name = array_key_first($tally);

        return ['name' => $name, 'count' => $tally[$name]];
    }

    /**
     * Item order counts, highest first.
     *
     * @return array<string, int>
     */
    public function itemFrequency(): array
    {
        $tally = [];

        foreach ($this as $record) {
            foreach ($record->itemNames() as $name) {
                $tally[$name] = ($tally[$name] ?? 0) + 1;
            }
        }

        arsort($tally);

        return $tally;
    }
}
