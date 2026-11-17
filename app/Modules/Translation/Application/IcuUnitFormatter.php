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
            // Quantity::toDecimalString() zachovává přesný desetinný zápis
            // i pro krajní hodnoty integeru; aplikační kód nepoužívá float.
            'count' => $quantity->toDecimalString(),
        ]);

        if (!is_string($formatted) || $formatter->getErrorCode() > U_ZERO_ERROR) {
            throw new UnitFormattingException(
                $locale->getCode(),
                $formatter->getErrorMessage(),
            );
        }

        return $formatted;
    }
}
