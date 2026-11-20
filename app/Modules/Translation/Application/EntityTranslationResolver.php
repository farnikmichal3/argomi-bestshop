<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Shared\Domain\Locale\Locale;

/**
 * Vybere překlad entity pro požadovaný jazyk s fallbackem na výchozí jazyk.
 *
 * Načtení obou řádků zajišťuje příslušný repozitář; tato třída nezná databázi
 * ani konkrétní typ překládané entity. Pokud jsou jazyky stejné, očekává
 * jediný výsledek v $requestedTranslation a druhý záznam ignoruje.
 */
final class EntityTranslationResolver
{
    /**
     * @template T of object
     *
     * @param T|null $requestedTranslation Překlad v požadovaném jazyce.
     * @param T|null $defaultTranslation Překlad ve výchozím jazyce.
     *
     * @return T|null Překlad požadovaného nebo výchozího jazyka.
     */
    public function resolve(
        Locale $requestedLocale,
        Locale $defaultLocale,
        ?object $requestedTranslation,
        ?object $defaultTranslation,
    ): ?object {
        // Stejný jazyk znamená jediný logický lookup. Chybějící překlad
        // proto nesmí být nahrazen druhou kopií téhož jazyka.
        if ($requestedLocale->equals($defaultLocale)) {
            return $requestedTranslation;
        }

        return $requestedTranslation ?? $defaultTranslation;
    }
}
