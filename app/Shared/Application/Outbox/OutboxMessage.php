<?php

declare(strict_types=1);

namespace App\Shared\Application\Outbox;

/**
 * Neměnný aplikační snímek zprávy převzaté z Transactional Outbox.
 *
 * Veřejný messageId je stabilní identifikátor pro idempotenci handleru.
 */
final readonly class OutboxMessage
{
    /**
     * @param array<string, mixed> $payload Data události bez tajných a citlivých údajů.
     */
    public function __construct(
        public string $messageId,
        public string $eventName,
        public array $payload,
        public int $attempt,
    ) {}
}
