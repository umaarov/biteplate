<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

/**
 * STATE — each table-lifecycle stage is a class implementing this interface.
 *
 * The {@see Table} delegates every action to its current state object, which
 * either performs the legal transition (by swapping the table's state) or
 * rejects it. The lifecycle rules therefore live in the state classes, not in a
 * sprawl of `if ($status === ...)` checks scattered across the codebase.
 *
 *   Free → Reserved → Occupied → AwaitingBill → Cleared → Free
 */
interface TableState
{
    public function status(): TableStatus;

    public function reserve(Table $table): void;

    public function occupy(Table $table, int $partySize): void;

    public function requestBill(Table $table): void;

    public function clear(Table $table): void;

    public function free(Table $table): void;
}
