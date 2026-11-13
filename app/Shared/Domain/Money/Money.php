<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use OverflowException;

/**
 * Neměnná peněžní částka uložená v nejmenších jednotkách dané měny.
 *
 * Například 1250 CZK znamená 12,50 Kč. Float se nepoužívá, aby výpočty
 * nezaváděly binární zaokrouhlovací chyby.
 */
final readonly class Money
{
    public function __construct(
        public int $minorAmount,
        public Currency $currency,
    ) {}

    /**
     * Sečte dvě částky stejné měny a před výpočtem ověří celočíselné přetečení.
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        // Hranice ověřujeme ještě před sčítáním, aby PHP nemohlo převést výsledek na float.
        if (
            ($other->minorAmount > 0 && $this->minorAmount > PHP_INT_MAX - $other->minorAmount)
            || ($other->minorAmount < 0 && $this->minorAmount < PHP_INT_MIN - $other->minorAmount)
        ) {
            throw new OverflowException('Money addition exceeds the integer range.');
        }

        return new self($this->minorAmount + $other->minorAmount, $this->currency);
    }

    /**
     * Odečte částku stejné měny a ohlídá dolní i horní mez integeru.
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        // Odečítání může přetéct oběma směry podle znaménka odečítané částky.
        if (
            ($other->minorAmount > 0 && $this->minorAmount < PHP_INT_MIN + $other->minorAmount)
            || ($other->minorAmount < 0 && $this->minorAmount > PHP_INT_MAX + $other->minorAmount)
        ) {
            throw new OverflowException('Money subtraction exceeds the integer range.');
        }

        return new self($this->minorAmount - $other->minorAmount, $this->currency);
    }

    /**
     * Vynásobí částku celočíselným faktorem bez ztráty přesnosti.
     */
    public function multiply(int $factor): self
    {
        $amount = $this->minorAmount;

        // Dělení mezní hodnotou umožní odhalit přetečení ještě před násobením.
        // Jednotlivé větve pokrývají všechny kombinace znamének částky a faktoru.
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

    /**
     * Vrátí zápornou, nulovou nebo kladnou hodnotu podle pořadí částek.
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorAmount <=> $other->minorAmount;
    }

    /**
     * Ověří shodu měny i samotné částky.
     */
    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency)
            && $this->minorAmount === $other->minorAmount;
    }

    /** Zjistí, zda je tato částka větší než zadaná částka stejné měny. */
    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /** Zjistí, zda je tato částka menší než zadaná částka stejné měny. */
    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /** Ověří, zda částka představuje přesně nulu. */
    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    /**
     * Zabrání aritmetickým operacím mezi různými měnami.
     */
    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatchException::forCurrencies($this->currency, $other->currency);
        }
    }
}
