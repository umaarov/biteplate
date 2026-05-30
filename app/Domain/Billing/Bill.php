<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * The finished, itemised bill — the immutable output of the {@see BillingFacade}.
 * A bill OWNS its {@see BillLineItem}s (composition) and exposes a ready-to-print
 * receipt and per-guest split shares.
 */
final readonly class Bill
{
    /**
     * @param list<BillLineItem> $lineItems
     * @param list<Money>        $splitShares
     * @param list<string>       $notes
     */
    public function __construct(
        public string $orderId,
        public int $tableNumber,
        public array $lineItems,
        public Money $subtotal,
        public Money $discount,
        public Money $taxable,
        public Money $tax,
        public float $taxRatePercent,
        public Money $tip,
        public Money $total,
        public int $splitWays,
        public array $splitShares,
        public string $pricingStrategy,
        public array $notes,
        public DateTimeImmutable $issuedAt,
    ) {
    }

    public function receipt(): string
    {
        $lines = [
            'BitePlate — Bill',
            sprintf('Order %s · Table %d', $this->orderId, $this->tableNumber),
            $this->issuedAt->format('D j M Y, H:i'),
            str_repeat('-', 38),
        ];

        foreach ($this->lineItems as $item) {
            $lines[] = sprintf('%-30s %7s', $item->label(), $item->amount->format());
        }

        $lines[] = str_repeat('-', 38);
        $lines[] = sprintf('%-30s %7s', 'Subtotal', $this->subtotal->format());

        if (! $this->discount->isZero()) {
            $label = $this->discount->isNegative() ? 'Surcharge' : 'Discount ('.$this->pricingStrategy.')';
            $lines[] = sprintf('%-30s %7s', $label, $this->discount->multiply(-1)->format());
        }

        $lines[] = sprintf('%-30s %7s', sprintf('VAT @ %g%%', $this->taxRatePercent), $this->tax->format());

        if (! $this->tip->isZero()) {
            $lines[] = sprintf('%-30s %7s', 'Tip', $this->tip->format());
        }

        $lines[] = str_repeat('=', 38);
        $lines[] = sprintf('%-30s %7s', 'TOTAL', $this->total->format());

        if ($this->splitWays > 1) {
            $lines[] = str_repeat('-', 38);
            $lines[] = sprintf('Split %d ways:', $this->splitWays);
            foreach ($this->splitShares as $i => $share) {
                $lines[] = sprintf('  Guest %d %26s', $i + 1, $share->format());
            }
        }

        foreach ($this->notes as $note) {
            $lines[] = '* '.$note;
        }

        return implode("\n", $lines);
    }
}
