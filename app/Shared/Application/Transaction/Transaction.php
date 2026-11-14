<?php

declare(strict_types=1);

namespace App\Shared\Application\Transaction;

/**
 * Aplikační rozhraní pro provedení operace v databázové transakci.
 *
 * Volající zná pouze tento kontrakt a nemusí znát databázovou vrstvu
 * ani konkrétní framework. Výsledek operace se vrací beze změny.
 */
interface Transaction
{
    /**
     * Provede operaci atomicky v rámci databázové transakce.
     *
     * Při úspěchu nejvyšší transakce se změny potvrdí. Pokud výjimka
     * nebo jiná chyba opustí nejvyšší transakční callback, implementace
     * zajistí rollback a chybu propaguje.
     *
     * Vnořená volání jsou povolena, ale jejich rollback není izolovaný:
     * pokud vnější callback zachytí chybu z vnořeného volání, změny
     * provedené uvnitř zůstávají součástí vnější transakce. Kontrakt
     * negarantuje savepointy ani samostatný rollback vnořené operace.
     *
     * @template TResult
     *
     * @param callable(): TResult $operation Operace, která se má provést.
     *
     * @return TResult Výsledek předaného callbacku.
     */
    public function run(callable $operation): mixed;
}
