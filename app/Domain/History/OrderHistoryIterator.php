<?php

declare(strict_types=1);

namespace App\Domain\History;

use Iterator;

/**
 * ITERATOR — a uniform cursor over order history records.
 *
 * Reporting code (Scenario D) walks the log through this iterator and never
 * touches the log's internal storage. If the {@see OrderHistoryLog} later moves
 * from a PHP array to a generator, a database cursor or a Kafka-sourced stream,
 * only this iterator changes — every report keeps working unmodified.
 *
 * @implements Iterator<int, OrderRecord>
 */
final class OrderHistoryIterator implements Iterator
{
    private int $cursor = 0;

    /**
     * @param list<OrderRecord> $records
     */
    public function __construct(private readonly array $records)
    {
    }

    public function current(): OrderRecord
    {
        return $this->records[$this->cursor];
    }

    public function key(): int
    {
        return $this->cursor;
    }

    public function next(): void
    {
        $this->cursor++;
    }

    public function rewind(): void
    {
        $this->cursor = 0;
    }

    public function valid(): bool
    {
        return array_key_exists($this->cursor, $this->records);
    }
}
