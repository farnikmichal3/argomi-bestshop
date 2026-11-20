<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

/**
 * Poskytuje měny dostupné pro obchod.
 *
 * Doména závisí pouze na tomto rozhraní; konkrétní zdroj měn
 * (například databáze) zůstává skrytý v infrastrukturní vrstvě.
 */
interface CurrencyProvider
{
    /**
     * Vrátí jedinou výchozí měnu obchodu.
     *
     * @throws DefaultCurrencyNotFoundException Pokud není výchozí měna nastavena.
     */
    public function getDefault(): Currency;

    /**
     * Vrátí všechny aktivní měny v pořadí určeném konfigurací obchodu.
     *
     * @return list<Currency>
     */
    public function getActive(): array;
}
