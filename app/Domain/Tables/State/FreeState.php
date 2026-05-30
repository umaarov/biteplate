<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

final class FreeState extends AbstractTableState
{
    public function status(): TableStatus
    {
        return TableStatus::Free;
    }

    public function reserve(Table $table): void
    {
        $table->setState(new ReservedState());
    }

    public function occupy(Table $table, int $partySize): void
    {
        $table->assignParty($partySize);
        $table->setState(new OccupiedState());
    }
}
