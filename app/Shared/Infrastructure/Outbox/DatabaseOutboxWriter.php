<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Domain\Clock\Clock;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Domain\PublicId\PublicIdGenerator;
use DateTimeZone;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Nette\Database\Explorer;

/**
 * Ukládá integrační události do databázové tabulky outbox_message.
 *
 * Writer záměrně nezačíná vlastní transakci. Vyžaduje již aktivní
 * transakci volající obchodní operace a zapisuje přes sdílený Explorer.
 */
final class DatabaseOutboxWriter implements OutboxWriter
{
    public function __construct(
        private readonly Explorer $database,
        private readonly Clock $clock,
        private readonly PublicIdGenerator $publicIdGenerator,
    ) {}

    /**
     * @throws InvalidArgumentException Pokud název události není platný.
     * @throws JsonException Pokud payload nelze bezpečně serializovat.
     * @throws LogicException Pokud není aktivní databázová transakce.
     */
    public function record(string $eventName, array $payload, ?int $shopOrderId = null): PublicId
    {
        $eventName = trim($eventName);

        if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,189}\z/', $eventName) !== 1) {
            throw new InvalidArgumentException('Název Outbox události musí být stabilní ASCII identifikátor o délce 1 až 190 znaků.');
        }

        if (!$this->database->getConnection()->getPdo()->inTransaction()) {
            throw new LogicException('Outbox událost lze zapsat pouze uvnitř aktivní databázové transakce.');
        }

        $messageId = $this->publicIdGenerator->generate();
        $payloadJson = json_encode((object) $payload, JSON_THROW_ON_ERROR);
        $createdAt = $this->clock->now()
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.u');

        $this->database->table('outbox_message')->insert([
            'message_id' => $messageId->toString(),
            'event_name' => $eventName,
            'shop_order_id' => $shopOrderId,
            'payload' => $payloadJson,
            'available_at' => $createdAt,
            'created_at' => $createdAt,
        ]);

        return $messageId;
    }
}
