<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

final class ReservedState extends AbstractTableState
{
    public function status(): TableStatus
    {
        return TableStatus::Reserved;
    }

    public function occupy(Table $table, int $partySize): void
    {
        $table->assignParty($partySize);
        $table->setState(new OccupiedState());
    }

    /** No-show or cancelled reservation releases the table. */
    public function free(Table $table): void
    {
        $table->releaseParty();
        $table->setState(new FreeState());
    }
}
