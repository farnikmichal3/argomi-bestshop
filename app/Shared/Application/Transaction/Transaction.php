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
     * Při úspěchu se transakce potvrdí. Pokud operace vyhodí výjimku
     * nebo jinou chybu, implementace zajistí rollback a chybu propaguje.
     * Vnořování transakcí se řídí možnostmi infrastrukturní implementace.
     *
     * @template TResult
     *
     * @param callable(): TResult $operation Operace, která se má provést.
     *
     * @return TResult Výsledek předaného callbacku.
     */
    public function run(callable $operation): mixed;
}
