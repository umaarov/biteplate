<?php

declare(strict_types=1);

namespace App\Livewire\Floor;

use App\Application\Auth\CurrentStaff;
use App\Application\Services\TableService;
use App\Domain\Shared\DomainException;
use App\Domain\Staff\Permission;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The restaurant floor. Each tile is a {@see \App\Domain\Tables\Table} and the
 * action buttons it offers are driven entirely by its State — illegal moves are
 * simply never presented, and the domain rejects them if attempted anyway.
 */
#[Layout('layouts.app')]
final class FloorBoard extends Component
{
    public ?int $seatingTable = null;

    public int $partySize = 2;

    public ?string $error = null;

    public function reserve(int $number): void
    {
        $this->run(fn (TableService $t) => $t->reserve($number));
    }

    public function startSeat(int $number): void
    {
        $this->seatingTable = $number;
        $this->partySize = 2;
    }

    public function confirmSeat(): void
    {
        $number = $this->seatingTable;
        if ($number === null) {
            return;
        }

        $this->run(function (TableService $t) use ($number): void {
            $t->seat($number, $this->partySize);
            $this->seatingTable = null;
        });
    }

    public function requestBill(int $number): void
    {
        $this->run(fn (TableService $t) => $t->requestBill($number));
    }

    public function clear(int $number): void
    {
        $this->run(fn (TableService $t) => $t->clear($number));
    }

    public function free(int $number): void
    {
        $this->run(fn (TableService $t) => $t->free($number));
    }

    public function takeOrder(int $number, CurrentStaff $current): mixed
    {
        if (! $current->can(Permission::TakeOrder)) {
            $this->error = 'Your role cannot take orders.';

            return null;
        }

        return $this->redirectRoute('order', ['table' => $number], navigate: true);
    }

    private function run(callable $action): void
    {
        $this->error = null;
        try {
            $action(app(TableService::class));
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(TableService $tables, CurrentStaff $currentStaff): View
    {
        return view('livewire.floor.floor-board', [
            'tables' => $tables->floor(),
            'currentStaff' => $currentStaff->staff(),
        ]);
    }
}
