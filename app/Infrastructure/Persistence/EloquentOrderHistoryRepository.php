<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Contracts\OrderHistoryRepository;
use App\Domain\History\OrderHistoryLog;
use App\Domain\History\OrderLineRecord;
use App\Domain\History\OrderRecord;
use App\Domain\Menu\MenuCategory;
use App\Domain\Shared\Money;
use App\Models\OrderHistoryEntry;
use DateTimeImmutable;

final class EloquentOrderHistoryRepository implements OrderHistoryRepository
{
    public function append(OrderRecord $record): void
    {
        OrderHistoryEntry::create([
            'order_id' => $record->orderId,
            'table_number' => $record->tableNumber,
            'staff_id' => $record->staffId,
            'lines' => array_map(static fn (OrderLineRecord $l) => [
                'name' => $l->name,
                'quantity' => $l->quantity,
                'line_total_minor' => $l->lineTotal->minorUnits,
                'category' => $l->category->value,
            ], $record->lines),
            'total_minor' => $record->total->minorUnits,
            'currency' => $record->total->currency,
            'pricing_strategy' => $record->pricingStrategy,
            'covers' => $record->covers,
            'cancelled' => $record->cancelled,
            'wasteful' => $record->wasteful,
            'placed_at' => $record->placedAt,
        ]);
    }

    public function loadInto(OrderHistoryLog $log): OrderHistoryLog
    {
        OrderHistoryLog::reset();
        $fresh = OrderHistoryLog::instance();

        OrderHistoryEntry::orderBy('placed_at')->each(function (OrderHistoryEntry $row) use ($fresh): void {
            $lines = array_map(
                static fn (array $l) => new OrderLineRecord(
                    $l['name'],
                    (int) $l['quantity'],
                    Money::fromMinor((int) $l['line_total_minor'], $row->currency),
                    MenuCategory::from($l['category']),
                ),
                $row->lines,
            );

            $fresh->append(new OrderRecord(
                orderId: $row->order_id,
                tableNumber: $row->table_number,
                staffId: $row->staff_id,
                lines: $lines,
                total: Money::fromMinor($row->total_minor, $row->currency),
                placedAt: $row->placed_at->toDateTimeImmutable(),
                pricingStrategy: $row->pricing_strategy,
                covers: $row->covers,
                cancelled: $row->cancelled,
                wasteful: $row->wasteful,
            ));
        });

        return $fresh;
    }
}
