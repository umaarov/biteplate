<?php

declare(strict_types=1);

namespace App\Domain\Kitchen\Command;

/**
 * COMMAND — a kitchen action reified as an object.
 *
 * Encapsulating "prepare this order", "cancel that one", "expedite the other"
 * as command objects lets the {@see \App\Domain\Kitchen\KitchenQueue} treat them
 * uniformly: log them, replay them, and — crucially — undo the last one. Each
 * command captures whatever it needs to reverse itself inside {@see execute()},
 * so {@see undo()} can restore the prior state precisely.
 */
interface KitchenCommand
{
    public function execute(): void;

    public function undo(): void;

    /** Short human-readable label for the command history / audit strip. */
    public function describe(): string;
}
