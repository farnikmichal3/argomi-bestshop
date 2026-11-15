<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Application\Outbox\OutboxMessage;
use App\Shared\Application\Outbox\OutboxMessageHandler;
use LogicException;
use Nette\Database\Explorer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Bezpečně vyzvedává a zpracovává zprávy z databázového Transactional Outboxu.
 *
 * Claim a změny stavů probíhají v krátkých DB transakcích. Handler se vždy
 * volá až po commitu claim transakce, tedy mimo databázovou transakci.
 */
final class DatabaseOutboxWorker
{
    private const DEFAULT_BATCH_SIZE = 10;
    private const MAX_BATCH_SIZE = 100;
    private const DEFAULT_MAX_ATTEMPTS = 8;
    private const DEFAULT_LEASE_SECONDS = 300;
    private const DEFAULT_BASE_RETRY_DELAY_SECONDS = 30;
    private const DEFAULT_MAX_RETRY_DELAY_SECONDS = 3600;

    /** @var array<string, OutboxMessageHandler> */
    private array $handlersByEventName = [];

    /**
     * @param iterable<OutboxMessageHandler> $handlers Handlery registrované přes DI tag outboxMessageHandler.
     */
    public function __construct(
        private readonly Explorer $database,
        iterable $handlers,
        private readonly LoggerInterface $logger,
        private readonly int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        private readonly int $leaseSeconds = self::DEFAULT_LEASE_SECONDS,
        private readonly int $baseRetryDelaySeconds = self::DEFAULT_BASE_RETRY_DELAY_SECONDS,
        private readonly int $maxRetryDelaySeconds = self::DEFAULT_MAX_RETRY_DELAY_SECONDS,
    ) {
        if (
            $this->maxAttempts < 1
            || $this->leaseSeconds < 1
            || $this->baseRetryDelaySeconds < 1
            || $this->maxRetryDelaySeconds < $this->baseRetryDelaySeconds
        ) {
            throw new \InvalidArgumentException('Konfigurace Outbox workeru obsahuje neplatné limity nebo časové intervaly.');
        }

        foreach ($handlers as $handler) {
            $eventName = $handler->eventName();

            if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,189}\z/', $eventName) !== 1) {
                throw new \InvalidArgumentException('Handler Outboxu musí mít platný stabilní identifikátor události.');
            }

            if (isset($this->handlersByEventName[$eventName])) {
                throw new LogicException(sprintf('Pro Outbox událost "%s" je registrováno více handlerů.', $eventName));
            }

            $this->handlersByEventName[$eventName] = $handler;
        }
    }

    /**
     * Provede jedno dávkové zpracování dostupných zpráv.
     *
     * Vrací počet převzatých zpráv. Selhání handleru jedné zprávy neukončí
     * zpracování dalších zpráv v dávce.
     *
     * @param list<string>|null $eventNames Omezení na konkrétní názvy událostí; null zpracuje všechny.
     */
    public function runOnce(int $batchSize = self::DEFAULT_BATCH_SIZE, ?array $eventNames = null): int
    {
        if ($batchSize < 1 || $batchSize > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException(sprintf('Velikost dávky musí být v rozsahu 1 až %d.', self::MAX_BATCH_SIZE));
        }

        $this->assertNoActiveTransaction();

        // Každou zprávu claimujeme až těsně před jejím zpracováním.
        // Kdybychom claimnuli celou dávku najednou, lease pozdějších zpráv
        // by mohl vypršet ještě během zpracování předchozích handlerů.
        $processed = 0;

        for ($i = 0; $i < $batchSize; $i++) {
            $messages = $this->claim(1, $eventNames);

            if ($messages === []) {
                break;
            }

            $this->process($messages[0]);
            $processed++;
        }

        return $processed;
    }

    /**
     * @param list<string>|null $eventNames Omezení na konkrétní názvy událostí; null zpracuje všechny.
     * @return list<ClaimedOutboxMessage>
     */
    private function claim(int $batchSize, ?array $eventNames): array
    {
        $result = $this->database->transaction(function () use ($batchSize, $eventNames): array {
            if ($eventNames === []) {
                return [];
            }

            $eventFilter = '';
            $eventParameters = [];

            if ($eventNames !== null) {
                foreach ($eventNames as $eventName) {
                    if (
                        !is_string($eventName)
                        || preg_match('/\\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,189}\\z/', $eventName) !== 1
                    ) {
                        throw new \InvalidArgumentException('Filtr Outbox událostí obsahuje neplatný identifikátor.');
                    }
                }

                $eventFilter = ' AND event_name IN (' . implode(', ', array_fill(0, count($eventNames), '?')) . ')';
                $eventParameters = $eventNames;
            }

            // Zpráva, jejíž poslední povolený pokus skončil pádem workeru,
            // už nesmí být znovu převzata. Po expiraci lease přejde do dead.
            $this->database->query(
                "UPDATE outbox_message
                SET status = 'dead',
                    dead_at = UTC_TIMESTAMP(6),
                    locked_until = NULL,
                    lock_token = NULL,
                    last_error = ?
                WHERE status = 'processing'
                    AND locked_until <= UTC_TIMESTAMP(6)
                    AND attempts >= ?"
                . $eventFilter,
                'Lease expired after maximum attempts.',
                $this->maxAttempts,
                ...$eventParameters,
            );

            // SKIP LOCKED dovoluje souběžným workerům převzít různé zprávy
            // bez čekání na řádky, které právě zamyká jiný worker.
            $parameters = [$this->maxAttempts, ...$eventParameters, $batchSize];

            $rows = $this->database->query(
                "SELECT id, message_id, event_name, payload, attempts
                FROM outbox_message
                WHERE attempts < ?
                    AND (
                        (status = 'pending' AND available_at <= UTC_TIMESTAMP(6))
                        OR
                        (status = 'processing' AND locked_until <= UTC_TIMESTAMP(6))
                    )"
                . $eventFilter
                . "
                ORDER BY id
                LIMIT ?
                FOR UPDATE SKIP LOCKED",
                ...$parameters,
            )->fetchAll();

            $claimedMessages = [];

            foreach ($rows as $row) {
                $idValue = $row->id;
                $attemptValue = $row->attempts;
                $payloadValue = $row->payload;
                $messageIdValue = $row->message_id;
                $eventNameValue = $row->event_name;

                if (
                    (!is_int($idValue) && !is_string($idValue))
                    || (!is_int($attemptValue) && !is_string($attemptValue))
                    || !is_string($payloadValue)
                    || !is_string($messageIdValue)
                    || !is_string($eventNameValue)
                ) {
                    throw new LogicException('Databáze vrátila neplatný tvar Outbox zprávy.');
                }

                $lockToken = random_bytes(16);
                $id = (int) $idValue;
                $attempt = (int) $attemptValue + 1;

                $updated = $this->database->query(
                    "UPDATE outbox_message
                    SET status = 'processing',
                        attempts = attempts + 1,
                        lock_token = ?,
                        locked_until = TIMESTAMPADD(SECOND, ?, UTC_TIMESTAMP(6))
                    WHERE id = ?
                        AND attempts < ?",
                    $lockToken,
                    $this->leaseSeconds,
                    $id,
                    $this->maxAttempts,
                );

                if ($updated->getRowCount() !== 1) {
                    continue;
                }

                $decodedPayload = json_decode($payloadValue, false, 512, JSON_THROW_ON_ERROR);

                if (!is_object($decodedPayload)) {
                    throw new LogicException('Payload Outbox zprávy není JSON objekt.');
                }

                /** @var array<string, mixed> $payload */
                $payload = (array) $decodedPayload;

                $claimedMessages[] = new ClaimedOutboxMessage(
                    $id,
                    $lockToken,
                    new OutboxMessage(
                        $messageIdValue,
                        $eventNameValue,
                        $payload,
                        $attempt,
                    ),
                );
            }

            return $claimedMessages;
        });

        if (!is_array($result)) {
            throw new LogicException('Outbox claim nevrátil seznam zpráv.');
        }

        foreach ($result as $claimedMessage) {
            if (!$claimedMessage instanceof ClaimedOutboxMessage) {
                throw new LogicException('Outbox claim vrátil neplatnou položku.');
            }
        }

        /** @var list<ClaimedOutboxMessage> $result */
        return $result;
    }

    private function process(ClaimedOutboxMessage $claimedMessage): void
    {
        $message = $claimedMessage->message;
        $handler = $this->handlersByEventName[$message->eventName] ?? null;

        try {
            if ($handler === null) {
                throw new LogicException(sprintf('Pro Outbox událost "%s" není registrován handler.', $message->eventName));
            }

            $handler->handle($message);
        } catch (Throwable $exception) {
            $this->assertNoActiveTransaction();
            $this->scheduleRetry($claimedMessage, $exception);

            return;
        }

        $this->assertNoActiveTransaction();

        if (!$this->markDelivered($claimedMessage)) {
            $this->logger->notice('Outbox lease expired before successful delivery could be acknowledged.', [
                'message_id' => $message->messageId,
                'event_name' => $message->eventName,
                'attempt' => $message->attempt,
            ]);
        }
    }

    private function markDelivered(ClaimedOutboxMessage $claimedMessage): bool
    {
        $result = $this->database->transaction(function () use ($claimedMessage): bool {
            $result = $this->database->query(
                "UPDATE outbox_message
                SET status = 'delivered',
                    delivered_at = UTC_TIMESTAMP(6),
                    locked_until = NULL,
                    lock_token = NULL,
                    last_error = NULL
                WHERE id = ?
                    AND status = 'processing'
                    AND lock_token = ?
                    AND locked_until > UTC_TIMESTAMP(6)",
                $claimedMessage->id,
                $claimedMessage->lockToken,
            );

            return $result->getRowCount() === 1;
        });

        if (!is_bool($result)) {
            throw new LogicException('Potvrzení Outbox zprávy nevrátilo boolean výsledek.');
        }

        return $result;
    }

    private function scheduleRetry(ClaimedOutboxMessage $claimedMessage, Throwable $exception): void
    {
        $message = $claimedMessage->message;
        $isDead = $message->attempt >= $this->maxAttempts;
        $nextStatus = $isDead ? 'dead' : 'pending';
        $retryDelay = $isDead ? 0 : $this->retryDelay($message->attempt);

        // Ukládáme pouze typ výjimky, nikoliv její text nebo payload.
        // Zprávy externích knihoven mohou obsahovat tokeny či osobní údaje.
        $errorType = substr($exception::class, 0, 1000);

        $updated = $this->database->transaction(function () use (
            $nextStatus,
            $retryDelay,
            $errorType,
            $claimedMessage,
        ): bool {
            $result = $this->database->query(
                "UPDATE outbox_message
                SET status = ?,
                    available_at = CASE
                        WHEN ? = 'pending' THEN TIMESTAMPADD(SECOND, ?, UTC_TIMESTAMP(6))
                        ELSE available_at
                    END,
                    dead_at = CASE
                        WHEN ? = 'dead' THEN UTC_TIMESTAMP(6)
                        ELSE NULL
                    END,
                    locked_until = NULL,
                    lock_token = NULL,
                    last_error = ?
                WHERE id = ?
                    AND status = 'processing'
                    AND lock_token = ?
                    AND locked_until > UTC_TIMESTAMP(6)",
                $nextStatus,
                $nextStatus,
                $retryDelay,
                $nextStatus,
                $errorType,
                $claimedMessage->id,
                $claimedMessage->lockToken,
            );

            return $result->getRowCount() === 1;
        });

        if (!is_bool($updated)) {
            throw new LogicException('Naplánování Outbox retry nevrátilo boolean výsledek.');
        }

        $context = [
            'message_id' => $message->messageId,
            'event_name' => $message->eventName,
            'attempt' => $message->attempt,
            'exception_type' => $errorType,
            'next_status' => $nextStatus,
        ];

        if (!$updated) {
            $this->logger->notice('Outbox lease expired before failure could be recorded.', $context);

            return;
        }

        $this->logger->warning('Outbox message handler failed.', $context);
    }

    /**
     * Worker nesmí běžet uvnitř cizí transakce; jinak by commit claimu
     * nemusel nastat před voláním handleru. Handler musí své transakce uzavřít.
     */
    private function assertNoActiveTransaction(): void
    {
        if ($this->database->getConnection()->getPdo()->inTransaction()) {
            throw new LogicException('Outbox worker nesmí pokračovat uvnitř již aktivní databázové transakce.');
        }
    }

    private function retryDelay(int $attempt): int
    {
        $delay = $this->baseRetryDelaySeconds;

        for ($i = 1; $i < $attempt && $delay < $this->maxRetryDelaySeconds; $i++) {
            $delay = min($this->maxRetryDelaySeconds, $delay * 2);
        }

        return $delay;
    }
}
