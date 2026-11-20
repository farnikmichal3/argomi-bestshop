<?php

declare(strict_types=1);

namespace App\Shared\Application\Outbox;

use App\Shared\Domain\PublicId\PublicId;

/**
 * Zapisuje integrační událost do Transactional Outbox.
 *
 * Implementace musí vložit záznam do stejné databázové transakce
 * jako obchodní změnu, která událost vyvolala.
 */
interface OutboxWriter
{
    /**
     * Zapíše událost a vrátí její stabilní veřejný identifikátor.
     *
     * Payload je vždy serializován jako JSON objekt, nikoliv JSON pole.
     *
     * @param array<string, mixed> $payload Data události bez tajných a citlivých údajů.
     * @param int|null $shopOrderId Objednávka pro události order.*; payload.orderId musí odpovídat a databáze odmítne demo objednávky.
     */
    public function record(string $eventName, array $payload, ?int $shopOrderId = null): PublicId;
}
