<?php

declare(strict_types=1);

namespace App\Domain\Tables;

use App\Domain\Shared\DomainException;
use App\Domain\Tables\State\FreeState;
use App\Domain\Tables\State\TableState;

/**
 * A dining table — the context object in the State pattern.
 *
 * It exposes intent-revealing actions (reserve, seat, requestBill, clear, free)
 * and delegates each to its current {@see TableState}. The table never decides
 * for itself whether a move is legal; the state does. The {@see setState()},
 * {@see assignParty()} and {@see releaseParty()} methods are the state objects'
 * private channel back into the context.
 */
final class Table
{
    private TableState $state;

    private ?int $partySize = null;

    public function __construct(
        private readonly int $number,
        private readonly int $capacity,
        ?TableState $initialState = null,
        private readonly ?string $section = null,
    ) {
        if ($capacity < 1) {
            throw new DomainException('Table capacity must be at least 1.');
        }

        $this->state = $initialState ?? new FreeState();
    }

    public function number(): int
    {
        return $this->number;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    /** Floor section label (Window, Bar, Garden…) — a display attribute. */
    public function section(): ?string
    {
        return $this->section;
    }

    public function status(): TableStatus
    {
        return $this->state->status();
    }

    public function partySize(): ?int
    {
        return $this->partySize;
    }

    // --- Intent-revealing actions, delegated to the current state ------------

    public function reserve(): void
    {
        $this->state->reserve($this);
    }

    public function seat(int $partySize): void
    {
        if ($partySize < 1) {
            throw new DomainException('A party must have at least one guest.');
        }

        if ($partySize > $this->capacity) {
            throw new DomainException(
                "Party of {$partySize} exceeds table {$this->number}'s capacity of {$this->capacity}."
            );
        }

        $this->state->occupy($this, $partySize);
    }

    public function requestBill(): void
    {
        $this->state->requestBill($this);
    }

    public function clear(): void
    {
        $this->state->clear($this);
    }

    public function free(): void
    {
        $this->state->free($this);
    }

    // --- State-only collaborators -------------------------------------------

    public function setState(TableState $state): void
    {
        $this->state = $state;
    }

    public function assignParty(int $partySize): void
    {
        $this->partySize = $partySize;
    }

    public function releaseParty(): void
    {
        $this->partySize = null;
    }
}
