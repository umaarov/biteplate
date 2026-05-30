<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\OrderHistoryRepository;
use App\Domain\History\OrderHistoryLog;
use App\Domain\History\OrderRecord;
use App\Domain\Shared\Money;

/**
 * The manager's reporting use-cases (Scenario C & D). Every figure is produced by
 * traversing the {@see OrderHistoryLog} through its ITERATOR — the analytics code
 * never touches how records are stored, so changing the storage format would not
 * break a single line here.
 */
final class AnalyticsService
{
    public function __construct(private readonly OrderHistoryRepository $history)
    {
    }

    /**
     * @return array{
     *   orders: int, covers: int, revenue: Money, food: Money, drinks: Money,
     *   avg_spend_per_table: Money, peak_hour: ?string, waste: int,
     *   top_items: array<string, int>, top_waiters: list<array{staff: string, covers: int}>
     * }
     */
    public function dashboard(): array
    {
        $log = $this->history->loadInto(OrderHistoryLog::instance());

        $revenue = Money::zero();
        $food = Money::zero();
        $drinks = Money::zero();
        $covers = 0;
        $orders = 0;
        $waste = 0;
        $perTable = [];
        $perHour = [];
        $perWaiter = [];

        // Single uniform traversal via the Iterator — the heart of Scenario D.
        foreach ($log as $record) {
            /** @var OrderRecord $record */
            $orders++;
            $covers += $record->covers;
            $revenue = $revenue->add($record->total);
            $food = $food->add($record->foodRevenue());
            $drinks = $drinks->add($record->drinkRevenue());

            if ($record->wasteful) {
                $waste++;
            }

            $perTable[$record->tableNumber] = ($perTable[$record->tableNumber] ?? 0) + $record->total->minorUnits;
            $hour = $record->placedAt->format('H:00');
            $perHour[$hour] = ($perHour[$hour] ?? 0) + 1;
            $perWaiter[$record->staffId] = ($perWaiter[$record->staffId] ?? 0) + $record->covers;
        }

        arsort($perWaiter);
        $topWaiters = [];
        foreach (array_slice($perWaiter, 0, 3, true) as $staff => $coversServed) {
            $topWaiters[] = ['staff' => (string) $staff, 'covers' => $coversServed];
        }

        $tableCount = max(count($perTable), 1);
        $peakHour = $perHour === [] ? null : array_key_first(array_filter($perHour, fn ($v) => $v === max($perHour)));

        return [
            'orders' => $orders,
            'covers' => $covers,
            'revenue' => $revenue,
            'food' => $food,
            'drinks' => $drinks,
            'avg_spend_per_table' => Money::fromMinor((int) round($revenue->minorUnits / $tableCount)),
            'peak_hour' => $peakHour,
            'waste' => $waste,
            'top_items' => array_slice($log->itemFrequency(), 0, 10, true),
            'top_waiters' => $topWaiters,
        ];
    }
}
