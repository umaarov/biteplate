<?php

declare(strict_types=1);

namespace App\Domain\Tables\State;

use App\Domain\Tables\TableStatus;

/** Maps a persisted {@see TableStatus} back to its concrete state object on load. */
final class TableStateFactory
{
    public static function for(TableStatus $status): TableState
    {
        return match ($status) {
            TableStatus::Free => new FreeState(),
            TableStatus::Reserved => new ReservedState(),
            TableStatus::Occupied => new OccupiedState(),
            TableStatus::AwaitingBill => new AwaitingBillState(),
            TableStatus::Cleared => new ClearedState(),
        };
    }
}
