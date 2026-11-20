<?php

declare(strict_types=1);

namespace App\Shared\Domain\Locale;

/**
 * Poskytuje výchozí a aktivní jazyky aktuálního obchodu.
 *
 * Rozhraní neodhaluje způsob uložení jazyků; implementace může číst databázi
 * nebo jiný důvěryhodný zdroj konfigurace.
 */
interface LocaleProvider
{
    /**
     * Vrátí jediný výchozí jazyk, který musí být zároveň aktivní.
     *
     * @throws DefaultLocaleNotFoundException Pokud výchozí aktivní jazyk chybí.
     */
    public function getDefault(): Locale;

    /**
     * Vrátí všechny aktivní jazyky v pořadí určeném správcem obchodu.
     *
     * @return list<Locale>
     */
    public function getActiveLocales(): array;

    /**
     * Ověří aktivitu jazyka bez ohledu na to, zda je Locale samo o sobě platné.
     */
    public function isActive(Locale $locale): bool;
}
