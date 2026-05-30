<?php

declare(strict_types=1);

namespace App\Livewire\Kitchen;

use App\Application\Auth\CurrentStaff;
use App\Application\Services\KitchenService;
use App\Domain\Shared\DomainException;
use App\Domain\Staff\Permission;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The kitchen display. Every button issues a {@see \App\Domain\Kitchen\Command\KitchenCommand}
 * through the {@see KitchenService}; "Undo" reverses the last one. Tickets are
 * grouped into station columns by the {@see \App\Domain\Kitchen\StationRouter}
 * (Scenario E) while the head chef gets the master list.
 */
#[Layout('layouts.app')]
final class KitchenBoard extends Component
{
    public ?string $cancelId = null;

    public string $cancelReason = '';

    public ?string $error = null;

    private function ensureCanManage(CurrentStaff $current): bool
    {
        if (! $current->can(Permission::ManageKitchenQueue)) {
            $this->error = 'Only the head chef or a manager can work the kitchen queue.';

            return false;
        }

        return true;
    }

    public function prepare(string $orderId, CurrentStaff $current): void
    {
        $this->guarded($current, fn (KitchenService $k) => $k->prepare($orderId, $current->staff()?->name() ?? 'Kitchen'));
    }

    public function markReady(string $orderId, CurrentStaff $current): void
    {
        $this->guarded($current, fn (KitchenService $k) => $k->markReady($orderId));
    }

    public function serve(string $orderId, CurrentStaff $current): void
    {
        $this->guarded($current, fn (KitchenService $k) => $k->serve($orderId));
    }

    public function startCancel(string $orderId): void
    {
        $this->cancelId = $orderId;
        $this->cancelReason = '';
    }

    public function confirmCancel(CurrentStaff $current): void
    {
        $id = $this->cancelId;
        if ($id === null) {
            return;
        }

        $this->guarded($current, function (KitchenService $k) use ($id): void {
            $k->cancel($id, $this->cancelReason ?: 'Cancelled by kitchen', 'Kitchen');
            $this->cancelId = null;
        });
    }

    public function undo(CurrentStaff $current): void
    {
        $this->guarded($current, fn (KitchenService $k) => $k->undoLast());
    }

    private function guarded(CurrentStaff $current, callable $action): void
    {
        $this->error = null;
        if (! $this->ensureCanManage($current)) {
            return;
        }

        try {
            $action(app(KitchenService::class));
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(KitchenService $kitchen): View
    {
        return view('livewire.kitchen.kitchen-board', [
            'board' => $kitchen->board(),
            'canUndo' => $kitchen->canUndo(),
        ]);
    }
}
