<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Ordering\Order;
use App\Domain\Pricing\LoyaltyTier;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingStrategy;
use DateTimeImmutable;

/**
 * FACADE — one simple call over a tangle of billing concerns.
 *
 * Behind {@see generate()} sit four collaborators that the caller never has to
 * orchestrate or even know about: the chosen {@see PricingStrategy} (discounts),
 * the {@see TaxCalculator} (VAT), the {@see TipCalculator}, and the
 * {@see SplitBillCalculator}. The cashier asks for "the bill for this order" and
 * gets a finished {@see Bill} back. Swapping the pricing strategy at the call
 * site is all it takes to re-price — the facade and the Bill are untouched, which
 * is the Strategy and Facade patterns reinforcing each other.
 */
final class BillingFacade
{
    public function __construct(
        private readonly TaxCalculator $tax = new TaxCalculator(),
        private readonly TipCalculator $tip = new TipCalculator(),
        private readonly SplitBillCalculator $splitter = new SplitBillCalculator(),
    ) {
    }

    public function generate(
        Order $order,
        PricingStrategy $strategy,
        int $partySize = 1,
        LoyaltyTier $loyalty = LoyaltyTier::None,
        ?float $tipPercent = null,
        int $splitWays = 1,
        ?DateTimeImmutable $at = null,
    ): Bill {
        $context = new PricingContext($order->pricedLines(), $partySize, $loyalty, $at);
        $pricing = $strategy->calculate($context);

        $discountedSubtotal = $pricing->total();
        $tax = $this->tax->on($discountedSubtotal);
        $tipAmount = $tipPercent === null
            ? $discountedSubtotal->subtract($discountedSubtotal) // zero, same currency
            : $this->tip->percentageOf($discountedSubtotal, $tipPercent);

        $total = $discountedSubtotal->add($tax)->add($tipAmount);

        $lineItems = array_map(
            static fn ($item) => new BillLineItem($item->component->name(), $item->quantity, $item->lineTotal()),
            $order->items(),
        );

        return new Bill(
            orderId: $order->id(),
            tableNumber: $order->tableNumber(),
            lineItems: $lineItems,
            subtotal: $pricing->subtotal,
            discount: $pricing->discount,
            taxable: $discountedSubtotal,
            tax: $tax,
            taxRatePercent: $this->tax->ratePercent(),
            tip: $tipAmount,
            total: $total,
            splitWays: $splitWays,
            splitShares: $this->splitter->split($total, $splitWays),
            pricingStrategy: $pricing->strategy,
            notes: $pricing->notes,
            issuedAt: $at ?? new DateTimeImmutable(),
        );
    }
}
