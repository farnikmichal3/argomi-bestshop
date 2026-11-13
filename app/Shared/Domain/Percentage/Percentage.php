<?php

declare(strict_types=1);

namespace App\Shared\Domain\Percentage;

use OverflowException;

/**
 * Neměnná procentní sazba s přesností jednoho bazického bodu.
 *
 * Interní hodnota je podepsaný počet bazických bodů: 1 bp odpovídá 0,01 %
 * a 10 000 bp odpovídá 100 %. Použití integeru vylučuje binární nepřesnost
 * desetinných čísel typu float.
 */
final readonly class Percentage
{
    private const int DECIMAL_PLACES = 2;

    public function __construct(
        public int $basisPoints,
    ) {}

    /**
     * Vytvoří sazbu z procentního zápisu bez zaokrouhlování.
     *
     * Přijímá tečku jako desetinný oddělovač a nejvýše dvě desetinná místa.
     * Zápis s čárkou, exponentem, mezerami nebo vyšší přesností odmítne.
     * Záporné hodnoty i hodnoty vyšší než 100 % jsou záměrně povoleny.
     */
    public static function fromPercent(string $value): self
    {
        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d{1,2}))?\z/', $value, $matches) !== 1) {
            throw InvalidPercentageException::forValue($value);
        }

        $isNegative = $matches[1] === '-';
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', self::DECIMAL_PLACES, '0');

        // Převod procent na bazické body sestavíme jako řetězec, abychom
        // ověřili rozsah před přetypováním a nikdy nepřetekli do floatu.
        $magnitude = ltrim($whole . $fraction, '0');
        $magnitude = $magnitude === '' ? '0' : $magnitude;
        $limit = $isNegative ? ltrim((string) PHP_INT_MIN, '-') : (string) PHP_INT_MAX;

        if (
            strlen($magnitude) > strlen($limit)
            || (strlen($magnitude) === strlen($limit) && strcmp($magnitude, $limit) > 0)
        ) {
            throw new OverflowException('Percentage value exceeds the integer range.');
        }

        $signedValue = ($isNegative && $magnitude !== '0' ? '-' : '') . $magnitude;

        return new self((int) $signedValue);
    }

    /**
     * Vytvoří sazbu přímo z celočíselného počtu bazických bodů.
     */
    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /**
     * Vrátí interní celočíselnou hodnotu v bazických bodech.
     */
    public function toBasisPoints(): int
    {
        return $this->basisPoints;
    }

    /**
     * Vrátí stabilní procentní zápis vždy se dvěma desetinnými místy.
     *
     * Řetězcové formátování bezpečně obslouží i PHP_INT_MIN, jehož absolutní
     * hodnota se nevejde do kladného rozsahu typu integer.
     */
    public function toPercentString(): string
    {
        $negative = $this->basisPoints < 0;
        $digits = ltrim((string) $this->basisPoints, '-');
        $digits = str_pad($digits, self::DECIMAL_PLACES + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -self::DECIMAL_PLACES);
        $fraction = substr($digits, -self::DECIMAL_PLACES);

        return ($negative ? '-' : '') . $whole . '.' . $fraction;
    }

    /**
     * Vrátí zápornou, nulovou nebo kladnou hodnotu podle pořadí sazeb.
     */
    public function compareTo(self $other): int
    {
        return $this->basisPoints <=> $other->basisPoints;
    }

    /**
     * Ověří shodu interní hodnoty v bazických bodech.
     */
    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }

    /** Ověří, zda sazba představuje přesně nula procent. */
    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }
}
