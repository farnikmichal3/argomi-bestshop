<?php

declare(strict_types=1);

namespace App\Shared\Application\Outbox;

/**
 * Aplikační kontrakt handleru jedné integrační události.
 *
 * Implementace musí být idempotentní podle OutboxMessage::messageId.
 * Worker garantuje pouze doručení at-least-once, nikoliv exactly-once.
 */
interface OutboxMessageHandler
{
    /**
     * Vrátí přesný stabilní identifikátor události, kterou handler zpracovává.
     */
    public function eventName(): string;

    /**
     * Zpracuje zprávu.
     *
     * Opakované zavolání se stejným messageId nesmí způsobit duplicitní účinek.
     */
    public function handle(OutboxMessage $message): void;
}
