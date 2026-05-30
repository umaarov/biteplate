<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

final class OccupiedState extends AbstractTableState
{
    public function status(): TableStatus
    {
        return TableStatus::Occupied;
    }

    public function requestBill(Table $table): void
    {
        $table->setState(new AwaitingBillState());
    }
}
