<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Shared\DomainException;
use App\Domain\Tables\Table;

/**
 * Default state behaviour: reject every transition. Concrete states override
 * only the moves that are legal from them, so an illegal action (e.g. clearing a
 * Free table) produces a clear, uniform error with zero boilerplate per state.
 */
abstract class AbstractTableState implements TableState
{
    public function reserve(Table $table): void
    {
        $this->reject('reserve');
    }

    public function occupy(Table $table, int $partySize): void
    {
        $this->reject('seat guests at');
    }

    public function requestBill(Table $table): void
    {
        $this->reject('request the bill for');
    }

    public function clear(Table $table): void
    {
        $this->reject('clear');
    }

    public function free(Table $table): void
    {
        $this->reject('free');
    }

    protected function reject(string $action): never
    {
        throw new DomainException(sprintf('Cannot %s a table that is %s.', $action, $this->status()->label()));
    }
}
