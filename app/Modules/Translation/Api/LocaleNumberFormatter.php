<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Money\Money;

/**
 * Veřejný kontrakt pro formátování čísel a peněžních částek podle locale.
 *
 * Locale se předává explicitně; implementace nesmí odvozovat jazyk
 * z globálního kontextu ani provádět měnové konverze.
 */
interface LocaleNumberFormatter
{
    /**
     * Naformátuje běžné číslo podle pravidel zadaného locale.
     */
    public function formatNumber(int|float $number, Locale $locale): string;

    /**
     * Naformátuje peněžní částku bez převodu minor units na float.
     *
     * Přesnost desetinné části určuje Currency::minorUnits.
     */
    public function formatMoney(Money $money, Locale $locale): string;
}
