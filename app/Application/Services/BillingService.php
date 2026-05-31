<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\OrderRepository;
use App\Application\Contracts\TableRepository;
use App\Domain\Billing\Bill as DomainBill;
use App\Domain\Billing\BillingFacade;
use App\Domain\Pricing\LoyaltyTier;
use App\Domain\Shared\DomainException;
use App\Models\Bill as BillModel;
use DateTimeImmutable;

/**
 * Use-cases for billing. Delegates all the hard sums to the {@see BillingFacade}
 * (tax + tip + split + the chosen pricing Strategy) and is responsible only for
 * loading the order, persisting the resulting bill and moving the table along its
 * lifecycle.
 */
final class BillingService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly TableRepository $tables,
        private readonly BillingFacade $facade,
        private readonly PricingStrategyRegistry $pricing,
    ) {
    }

    public function preview(
        string $orderId,
        string $strategyKey = 'auto',
        ?float $tipPercent = null,
        int $splitWays = 1,
        LoyaltyTier $loyalty = LoyaltyTier::None,
    ): DomainBill {
        $order = $this->orders->find($orderId) ?? throw new DomainException("Order {$orderId} not found.");
        $table = $this->tables->find($order->tableNumber());
        $partySize = $table?->partySize() ?? 1;

        return $this->facade->generate(
            order: $order,
            strategy: $this->pricing->make($strategyKey, new DateTimeImmutable()),
            partySize: $partySize,
            loyalty: $loyalty,
            tipPercent: $tipPercent,
            splitWays: $splitWays,
        );
    }

    /** Generate, persist the bill, and move the table to Awaiting Bill. */
    public function finalize(
        string $orderId,
        string $strategyKey = 'auto',
        ?float $tipPercent = null,
        int $splitWays = 1,
        LoyaltyTier $loyalty = LoyaltyTier::None,
    ): DomainBill {
        $bill = $this->preview($orderId, $strategyKey, $tipPercent, $splitWays, $loyalty);

        BillModel::updateOrCreate(
            ['order_id' => $bill->orderId],
            [
                'subtotal_minor' => $bill->subtotal->minorUnits,
                'discount_minor' => $bill->discount->minorUnits,
                'tax_minor' => $bill->tax->minorUnits,
                'tax_rate' => $bill->taxRatePercent,
                'tip_minor' => $bill->tip->minorUnits,
                'total_minor' => $bill->total->minorUnits,
                'currency' => $bill->total->currency,
                'split_ways' => $bill->splitWays,
                'split_shares' => array_map(fn ($m) => $m->minorUnits, $bill->splitShares),
                'pricing_strategy' => $bill->pricingStrategy,
                'notes' => $bill->notes,
                'issued_at' => $bill->issuedAt,
            ],
        );

        $table = $this->tables->find($bill->tableNumber);
        if ($table !== null && $table->status() === \App\Domain\Tables\TableStatus::Occupied) {
            $table->requestBill();
            $this->tables->save($table);
        }

        return $bill;
    }

    /** Settle up: mark the table's orders paid, then clear and free the table. */
    public function close(int $tableNumber): void
    {
        foreach ($this->orders->billableForTable($tableNumber) as $order) {
            $order->markPaid();
            $this->orders->save($order);
        }

        $table = $this->tables->find($tableNumber) ?? throw new DomainException("Table {$tableNumber} not found.");

        // Walk the State machine to a clean table whether the cashier issued the
        // bill first (Awaiting Bill) or is settling straight from Occupied.
        if ($table->status() === \App\Domain\Tables\TableStatus::Occupied) {
            $table->requestBill();
        }
        $table->clear();
        $table->free();
        $this->tables->save($table);
    }
}
