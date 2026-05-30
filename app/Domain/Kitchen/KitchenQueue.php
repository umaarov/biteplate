<?php

declare(strict_types=1);

namespace App\Domain\Kitchen;

use App\Domain\Kitchen\Command\KitchenCommand;
use App\Domain\Ordering\Order;
use App\Domain\Shared\DomainException;

/**
 * The INVOKER in the Command pattern, and the kitchen's working board.
 *
 * Two responsibilities, deliberately kept together because they are one concept
 * in a kitchen:
 *
 *  1. It holds the ordered board of tickets the team works through, and lets
 *     commands reprioritise it (expedite).
 *  2. It runs {@see KitchenCommand}s, keeping an undo stack (and a redo stack)
 *     so the head chef can take back the last action.
 *
 * It knows nothing about cooking — it only sequences commands and exposes a
 * history. That separation is exactly what the Command pattern buys us.
 */
final class KitchenQueue
{
    /** @var list<Order> The live board, in preparation order. */
    private array $board = [];

    /** @var list<KitchenCommand> */
    private array $undoStack = [];

    /** @var list<KitchenCommand> */
    private array $redoStack = [];

    /** @var list<string> Append-only log of everything that has happened. */
    private array $log = [];

    /** Put a freshly-sent order onto the board. */
    public function enqueue(Order $order): void
    {
        $this->board[] = $order;
        $this->log[] = 'Enqueued order '.$order->id();
    }

    /** Execute a command and record it so it can be undone. */
    public function run(KitchenCommand $command): void
    {
        $command->execute();
        $this->undoStack[] = $command;
        $this->redoStack = [];
        $this->log[] = $command->describe();
    }

    public function canUndo(): bool
    {
        return $this->undoStack !== [];
    }

    public function undoLast(): void
    {
        if (! $this->canUndo()) {
            throw new DomainException('There is nothing to undo.');
        }

        $command = array_pop($this->undoStack);
        $command->undo();
        $this->redoStack[] = $command;
        $this->log[] = 'UNDO — '.$command->describe();
    }

    public function canRedo(): bool
    {
        return $this->redoStack !== [];
    }

    public function redoLast(): void
    {
        if (! $this->canRedo()) {
            throw new DomainException('There is nothing to redo.');
        }

        $command = array_pop($this->redoStack);
        $command->execute();
        $this->undoStack[] = $command;
        $this->log[] = 'REDO — '.$command->describe();
    }

    // --- Board access, used by ExpediteOrderCommand --------------------------

    /** @return list<Order> */
    public function board(): array
    {
        return $this->board;
    }

    public function positionOf(Order $order): int
    {
        foreach ($this->board as $index => $candidate) {
            if ($candidate === $order) {
                return $index;
            }
        }

        throw new DomainException("Order {$order->id()} is not on the kitchen board.");
    }

    public function moveToFront(Order $order): void
    {
        $index = $this->positionOf($order);
        unset($this->board[$index]);
        array_unshift($this->board, $order);
        $this->board = array_values($this->board);
    }

    public function insertAt(Order $order, int $index): void
    {
        $this->board = array_values(array_filter($this->board, static fn (Order $o) => $o !== $order));
        $index = max(0, min($index, count($this->board)));
        array_splice($this->board, $index, 0, [$order]);
    }

    /** @return list<string> */
    public function log(): array
    {
        return $this->log;
    }
}
