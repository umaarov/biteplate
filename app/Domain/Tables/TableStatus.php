<?php

declare(strict_types=1);

namespace App\Domain\Tables;

enum TableStatus: string
{
    case Free = 'free';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case AwaitingBill = 'awaiting_bill';
    case Cleared = 'cleared';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Reserved => 'Reserved',
            self::Occupied => 'Occupied',
            self::AwaitingBill => 'Awaiting Bill',
            self::Cleared => 'Cleared',
        };
    }

    /** Tailwind-ish token the UI maps to a colour — kept here so status/colour never drift apart. */
    public function tone(): string
    {
        return match ($this) {
            self::Free => 'emerald',
            self::Reserved => 'amber',
            self::Occupied => 'sky',
            self::AwaitingBill => 'violet',
            self::Cleared => 'zinc',
        };
    }
}
