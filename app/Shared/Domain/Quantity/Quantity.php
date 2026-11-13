<?php

declare(strict_types=1);

namespace App\Shared\Domain\Quantity;

use OverflowException;

/**
 * Neměnné množství s pevnou přesností tří desetinných míst.
 *
 * Hodnota se ukládá jako celé číslo v tisícinách. Například 1,250 je
 * reprezentováno hodnotou 1250. Desetinná čísla typu float se nepoužívají,
 * aby výpočty nebyly ovlivněny binární nepřesností.
 */
final readonly class Quantity
{
    private const int SCALE = 1000;
    private const int DECIMAL_PLACES = 3;

    public function __construct(
        public int $milliUnits,
    ) {}

    /**
     * Vytvoří množství z desetinného řetězce bez zaokrouhlování.
     *
     * Přijímá tečku jako desetinný oddělovač a nejvýše tři desetinná místa.
     * Zápis s čárkou, exponentem, mezerami nebo více desetinnými místy odmítne.
     */
    public static function fromDecimal(string $value): self
    {
        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d{1,3}))?\z/', $value, $matches) !== 1) {
            throw InvalidQuantityException::forValue($value);
        }

        $isNegative = $matches[1] === '-';
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', self::DECIMAL_PLACES, '0');

        // Řetězcové sestavení umožňuje ověřit rozsah ještě před převodem na integer.
        $magnitude = ltrim($whole . $fraction, '0');
        $magnitude = $magnitude === '' ? '0' : $magnitude;
        $limit = $isNegative ? '9223372036854775808' : (string) PHP_INT_MAX;

        if (
            strlen($magnitude) > strlen($limit)
            || (strlen($magnitude) === strlen($limit) && strcmp($magnitude, $limit) > 0)
        ) {
            throw new OverflowException('Quantity decimal value exceeds the integer range.');
        }

        $signedValue = ($isNegative && $magnitude !== '0' ? '-' : '') . $magnitude;

        return new self((int) $signedValue);
    }

    /**
     * Vytvoří množství z celého čísla a bezpečně jej převede na tisíciny.
     */
    public static function fromInteger(int $value): self
    {
        // Kontrola před násobením zabrání tomu, aby PHP převedlo výsledek na float.
        if (
            $value > intdiv(PHP_INT_MAX, self::SCALE)
            || $value < intdiv(PHP_INT_MIN, self::SCALE)
        ) {
            throw new OverflowException('Quantity integer value exceeds the integer range.');
        }

        return new self($value * self::SCALE);
    }

    /**
     * Sečte dvě množství a před výpočtem ověří celočíselné přetečení.
     */
    public function add(self $other): self
    {
        if (
            ($other->milliUnits > 0 && $this->milliUnits > PHP_INT_MAX - $other->milliUnits)
            || ($other->milliUnits < 0 && $this->milliUnits < PHP_INT_MIN - $other->milliUnits)
        ) {
            throw new OverflowException('Quantity addition exceeds the integer range.');
        }

        return new self($this->milliUnits + $other->milliUnits);
    }

    /**
     * Odečte druhé množství a ohlídá dolní i horní mez integeru.
     */
    public function subtract(self $other): self
    {
        if (
            ($other->milliUnits > 0 && $this->milliUnits < PHP_INT_MIN + $other->milliUnits)
            || ($other->milliUnits < 0 && $this->milliUnits > PHP_INT_MAX + $other->milliUnits)
        ) {
            throw new OverflowException('Quantity subtraction exceeds the integer range.');
        }

        return new self($this->milliUnits - $other->milliUnits);
    }

    /**
     * Vynásobí množství celočíselným faktorem bez změny desetinné přesnosti.
     */
    public function multiply(int $factor): self
    {
        $amount = $this->milliUnits;

        // Dělení mezní hodnotou odhalí přetečení ještě před samotným násobením.
        // Větve pokrývají kladné i záporné hodnoty a všechny kombinace znamének.
        if (
            ($amount > 0 && $factor > 0 && $amount > intdiv(PHP_INT_MAX, $factor))
            || ($amount > 0 && $factor < 0 && $factor < intdiv(PHP_INT_MIN, $amount))
            || ($amount < 0 && $factor > 0 && $amount < intdiv(PHP_INT_MIN, $factor))
            || ($amount < 0 && $factor < 0 && $amount < intdiv(PHP_INT_MAX, $factor))
        ) {
            throw new OverflowException('Quantity multiplication exceeds the integer range.');
        }

        return new self($amount * $factor);
    }

    /**
     * Vrátí zápornou, nulovou nebo kladnou hodnotu podle pořadí množství.
     */
    public function compareTo(self $other): int
    {
        return $this->milliUnits <=> $other->milliUnits;
    }

    /**
     * Ověří shodu interní hodnoty.
     */
    public function equals(self $other): bool
    {
        return $this->milliUnits === $other->milliUnits;
    }

    /** Ověří, zda množství představuje přesně nulu. */
    public function isZero(): bool
    {
        return $this->milliUnits === 0;
    }

    /**
     * Vrátí desetinný zápis vždy se třemi místy a tečkou jako oddělovačem.
     */
    public function toDecimalString(): string
    {
        // Řetězcová práce bezpečně obslouží i PHP_INT_MIN, jehož absolutní hodnota
        // se nevejde do kladného rozsahu typu integer.
        $negative = $this->milliUnits < 0;
        $digits = ltrim((string) $this->milliUnits, '-');
        $digits = str_pad($digits, self::DECIMAL_PLACES + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -self::DECIMAL_PLACES);
        $fraction = substr($digits, -self::DECIMAL_PLACES);

        return ($negative ? '-' : '') . $whole . '.' . $fraction;
    }
}
