<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

/**
 * The order lifecycle. Transitions are guarded by {@see canTransitionTo()} so an
 * order can never jump illegally (e.g. straight from Draft to Served). Modelled
 * here as a backed enum with an explicit transition table — a pragmatic, fully
 * type-safe expression of the State pattern's intent for an essentially linear
 * lifecycle. (The richer object-per-state form is shown on {@see \App\Domain\Tables\Table}.)
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case SentToKitchen = 'sent_to_kitchen';
    case InPreparation = 'in_preparation';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::SentToKitchen => 'Sent to kitchen',
            self::InPreparation => 'In preparation',
            self::Ready => 'Ready to serve',
            self::Served => 'Served',
            self::Cancelled => 'Cancelled',
        };
    }

    /** @return list<OrderStatus> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::SentToKitchen, self::Cancelled],
            self::SentToKitchen => [self::InPreparation, self::Cancelled],
            self::InPreparation => [self::Ready, self::Cancelled],
            self::Ready => [self::Served, self::Cancelled],
            self::Served, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** Only a Draft order may have its items modified — once it is with the kitchen it is locked. */
    public function isModifiable(): bool
    {
        return $this === self::Draft;
    }

    /** Cancelling at or after preparation is a waste event the end-of-night report flags (Scenario D). */
    public function cancellationWouldWaste(): bool
    {
        return in_array($this, [self::InPreparation, self::Ready], true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Served || $this === self::Cancelled;
    }
}
