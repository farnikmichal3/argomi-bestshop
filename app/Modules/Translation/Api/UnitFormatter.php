<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Quantity\Quantity;

/**
 * Veřejný kontrakt pro výběr lokalizovaného symbolu nebo názvu jednotky.
 *
 * ICU vzor názvu i locale dodává volající; překladové řádky načítá vlastnický
 * repozitář jednotek a modul Translation neprovádí databázové dotazy.
 */
interface UnitFormatter
{
    /**
     * Vrátí symbol jednotky uložený pro konkrétní locale.
     */
    public function formatSymbol(string $symbol): string;

    /**
     * Vybere správný množný tvar názvu podle přesného množství a locale.
     *
     * Množství se předává ICU jako desetinný řetězec z celočíselných tisícin,
     * nikoliv jako float.
     */
    public function formatName(string $namePattern, Quantity $quantity, Locale $locale): string;
}
