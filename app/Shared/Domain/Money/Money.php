<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use OverflowException;

final readonly class Money
{
    public function __construct(
        public int $minorAmount,
        public Currency $currency,
    ) {
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        if (
            ($other->minorAmount > 0 && $this->minorAmount > PHP_INT_MAX - $other->minorAmount)
            || ($other->minorAmount < 0 && $this->minorAmount < PHP_INT_MIN - $other->minorAmount)
        ) {
            throw new OverflowException('Money addition exceeds the integer range.');
        }

        return new self($this->minorAmount + $other->minorAmount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if (
            ($other->minorAmount > 0 && $this->minorAmount < PHP_INT_MIN + $other->minorAmount)
            || ($other->minorAmount < 0 && $this->minorAmount > PHP_INT_MAX + $other->minorAmount)
        ) {
            throw new OverflowException('Money subtraction exceeds the integer range.');
        }

        return new self($this->minorAmount - $other->minorAmount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        $amount = $this->minorAmount;

        if (
            ($amount > 0 && $factor > 0 && $amount > intdiv(PHP_INT_MAX, $factor))
            || ($amount > 0 && $factor < 0 && $factor < intdiv(PHP_INT_MIN, $amount))
            || ($amount < 0 && $factor > 0 && $amount < intdiv(PHP_INT_MIN, $factor))
            || ($amount < 0 && $factor < 0 && $amount < intdiv(PHP_INT_MAX, $factor))
        ) {
            throw new OverflowException('Money multiplication exceeds the integer range.');
        }

        return new self($amount * $factor, $this->currency);
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorAmount <=> $other->minorAmount;
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency)
            && $this->minorAmount === $other->minorAmount;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatchException::forCurrencies($this->currency, $other->currency);
        }
    }
}
