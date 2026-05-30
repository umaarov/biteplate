<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\History\OrderHistoryLog;
use App\Domain\History\OrderRecord;

/**
 * Durable backing store for the order history. The in-memory
 * {@see OrderHistoryLog} singleton is the domain-facing accessor; this port
 * persists records and rehydrates the singleton from the database so analytics
 * survive process restarts.
 */
interface OrderHistoryRepository
{
    public function append(OrderRecord $record): void;

    /** Reset and repopulate the singleton from durable storage, returning it. */
    public function loadInto(OrderHistoryLog $log): OrderHistoryLog;
}
