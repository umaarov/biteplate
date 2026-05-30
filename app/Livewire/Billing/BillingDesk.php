<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Application\Auth\CurrentStaff;
use App\Application\Contracts\OrderRepository;
use App\Application\Services\BillingService;
use App\Application\Services\PricingStrategyRegistry;
use App\Domain\Shared\DomainException;
use App\Domain\Staff\Permission;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The cashier's POS. Picking a strategy from the dropdown swaps the
 * {@see \App\Domain\Pricing\PricingStrategy} the {@see BillingService} hands to
 * the {@see \App\Domain\Billing\BillingFacade} at runtime — no billing code
 * changes. Tax, tip and bill-splitting all happen behind the single facade call.
 */
#[Layout('layouts.app')]
final class BillingDesk extends Component
{
    #[Url]
    public ?int $table = null;

    public ?string $orderId = null;

    public string $strategy = 'auto';

    public int $tip = 0;

    public int $splitWays = 1;

    public ?string $error = null;

    public function mount(OrderRepository $orders): void
    {
        if ($this->table !== null) {
            $this->orderId = $orders->billableForTable($this->table)[0]?->id();
        }
    }

    public function select(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->error = null;
    }

    public function finalize(CurrentStaff $current, BillingService $billing): void
    {
        if (! $this->guardClose($current) || $this->orderId === null) {
            return;
        }

        try {
            $billing->finalize($this->orderId, $this->strategy, $this->tip ?: null, max(1, $this->splitWays));
            session()->flash('status', "Bill issued for {$this->orderId}.");
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function closeTable(int $number, CurrentStaff $current, BillingService $billing): void
    {
        if (! $this->guardClose($current)) {
            return;
        }

        try {
            $billing->close($number);
            $this->orderId = null;
            session()->flash('status', "Table {$number} settled and cleared.");
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    private function guardClose(CurrentStaff $current): bool
    {
        if (! $current->can(Permission::CloseBill)) {
            $this->error = 'Your role can view bills but not close them.';

            return false;
        }

        return true;
    }

    public function render(OrderRepository $orders, BillingService $billing, PricingStrategyRegistry $pricing, CurrentStaff $current): View
    {
        $bill = null;
        if ($this->orderId !== null) {
            try {
                $bill = $billing->preview($this->orderId, $this->strategy, $this->tip ?: null, max(1, $this->splitWays));
            } catch (DomainException $e) {
                $this->error = $e->getMessage();
            }
        }

        return view('livewire.billing.billing-desk', [
            'billable' => $orders->allBillable(),
            'bill' => $bill,
            'strategies' => $pricing->options(),
            'canClose' => $current->can(Permission::CloseBill),
        ]);
    }
}
