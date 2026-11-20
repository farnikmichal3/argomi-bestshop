<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Application\Outbox\OutboxMessage;

/**
 * Interní snímek zprávy spolu s lease tokenem konkrétního převzetí.
 *
 * Binární token se nikdy nepředává aplikačnímu handleru.
 */
final readonly class ClaimedOutboxMessage
{
    public function __construct(
        public int $id,
        public string $lockToken,
        public OutboxMessage $message,
    ) {}
}
