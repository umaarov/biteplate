<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;

final class AwaitingBillState extends AbstractTableState
{
    public function status(): TableStatus
    {
        return TableStatus::AwaitingBill;
    }

    public function clear(Table $table): void
    {
        $table->releaseParty();
        $table->setState(new ClearedState());
    }
}
