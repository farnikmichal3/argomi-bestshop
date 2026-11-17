<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Api\UnitFormatter;
use App\Modules\Translation\Api\UnitFormattingException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Quantity\Quantity;
use MessageFormatter;
use Throwable;

/**
 * Formátuje názvy jednotek pomocí ICU MessageFormat.
 *
 * Překladový vzor zůstává databázovým údajem. Tato služba pouze aplikuje
 * pravidla množného čísla pro explicitně předané locale.
 */
final class IcuUnitFormatter implements UnitFormatter
{
    public function formatSymbol(string $symbol): string
    {
        return $symbol;
    }

    public function formatName(string $namePattern, Quantity $quantity, Locale $locale): string
    {
        if (!self::isWithinReliableIcuRange($quantity)) {
            throw new UnitFormattingException(
                $locale->getCode(),
                'Quantity exceeds the exact decimal precision supported by ICU plural selection.',
            );
        }

        try {
            $formatter = new MessageFormatter($locale->getCode(), $namePattern);
        } catch (Throwable $exception) {
            throw new UnitFormattingException(
                $locale->getCode(),
                $exception->getMessage(),
                $exception,
            );
        }

        $formatted = $formatter->format([
            // Desetinný řetězec zachovává přesnost i pro krajní integerové
            // hodnoty. Koncové nuly odstraníme, protože interní měřítko
            // Quantity (tisíciny) samo o sobě nemá měnit pluralizační kategorii.
            'count' => self::toCanonicalDecimal($quantity),
        ]);

        if ($formatter->getErrorCode() > U_ZERO_ERROR) {
            throw new UnitFormattingException(
                $locale->getCode(),
                $formatter->getErrorMessage(),
            );
        }

        return $formatted;
    }

    /**
     * Ověří rozsah, ve kterém převod desetinného řetězce na ICU číslo
     * zachová rozlišení všech tří desetinných míst.
     *
     * Při absolutní hodnotě menší než 2^41 je rozestup sousedních hodnot
     * IEEE 754 double nejvýše 0,00048828125, tedy menší než 0,001.
     * Kontrola pracuje výhradně s řetězcem; Quantity se nepřevádí na float.
     */
    private static function isWithinReliableIcuRange(Quantity $quantity): bool
    {
        $decimal = $quantity->toDecimalString();
        $unsigned = ltrim($decimal, '-');
        $whole = ltrim(explode('.', $unsigned, 2)[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        $maximumWhole = '2199023255551';

        return strlen($whole) < strlen($maximumWhole)
            || (strlen($whole) === strlen($maximumWhole) && strcmp($whole, $maximumWhole) <= 0);
    }

    /**
     * Převede pevný tří místný zápis na nejkratší přesný desetinný řetězec.
     *
     * Například 1.000 převede na 1 a 1.250 na 1.25.
     */
    private static function toCanonicalDecimal(Quantity $quantity): string
    {
        $decimal = rtrim($quantity->toDecimalString(), '0');

        return rtrim($decimal, '.');
    }
}
