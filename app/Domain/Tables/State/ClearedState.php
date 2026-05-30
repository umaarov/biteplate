<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

final class ClearedState extends AbstractTableState
{
    public function status(): TableStatus
    {
        return TableStatus::Cleared;
    }

    /** Once bussed and reset, the table returns to the pool. */
    public function free(Table $table): void
    {
        $table->setState(new FreeState());
    }
}
