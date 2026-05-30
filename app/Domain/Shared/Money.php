<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Immutable money value object.
 *
 * Amounts are stored as integer minor units (e.g. cents) to avoid floating
 * point rounding errors — a non-negotiable rule for any billing system.
 * Every arithmetic operation returns a new instance, so a Money can never be
 * mutated out from under the code that is holding it (cf. encapsulation).
 */
final readonly class Money
{
    private function __construct(
        public int $minorUnits,
        public string $currency,
    ) {
        if ($currency === '' || strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO code.');
        }
    }

    public static function of(int|float $major, string $currency = 'GBP'): self
    {
        return new self((int) round($major * 100), strtoupper($currency));
    }

    public static function fromMinor(int $minorUnits, string $currency = 'GBP'): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public static function zero(string $currency = 'GBP'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /** Multiply by a scalar (quantity) and round half-up. */
    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->minorUnits * $factor), $this->currency);
    }

    /** Return $percent of this amount (e.g. 20.0 => 20%). */
    public function percentage(float $percent): self
    {
        return new self((int) round($this->minorUnits * $percent / 100), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits > $other->minorUnits;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minorUnits === $other->minorUnits;
    }

    public function toFloat(): float
    {
        return $this->minorUnits / 100;
    }

    public function format(): string
    {
        $symbol = match ($this->currency) {
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency.' ',
        };

        return $symbol.number_format($this->minorUnits / 100, 2);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on mismatched currencies {$this->currency} and {$other->currency}."
            );
        }
    }
}
