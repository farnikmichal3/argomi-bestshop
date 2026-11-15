--TEST--
Transactional Outbox worker claims, delivers, retries and protects lease ownership
--FILE--
<?php

use Tests\Support\IntegrationTestBootstrap;

use App\Shared\Application\Outbox\OutboxMessage;
use App\Shared\Application\Outbox\OutboxMessageHandler;
use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Infrastructure\Outbox\DatabaseOutboxWorker;
use Nette\Database\Explorer;
use Psr\Log\NullLogger;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;

/** @var Transaction $transaction */
$transaction = $container->getByType(Transaction::class);

/** @var OutboxWriter $writer */
$writer = $container->getByType(OutboxWriter::class);

/**
 * Vloží testovací zprávu stejnou cestou jako obchodní aplikační operace.
 */
$createMessage = static function (string $eventName) use ($transaction, $writer): PublicId {
    return $transaction->run(
        static fn (): PublicId => $writer->record($eventName, ['test' => true]),
    );
};

$ids = [];

try {
    $failureId = $createMessage('test.outbox.worker.failure');
    $ids[] = $failureId;

    $successId = $createMessage('test.outbox.worker.success');
    $ids[] = $successId;

    $failureHandler = new class implements OutboxMessageHandler {
        /** @var list<OutboxMessage> */
        public array $handledMessages = [];

        public function eventName(): string
        {
            return 'test.outbox.worker.failure';
        }

        public function handle(OutboxMessage $message): void
        {
            $this->handledMessages[] = $message;
            throw new RuntimeException('This exception text must not be stored in the Outbox.');
        }
    };

    $successHandler = new class implements OutboxMessageHandler {
        public ?OutboxMessage $handledMessage = null;

        public function eventName(): string
        {
            return 'test.outbox.worker.success';
        }

        public function handle(OutboxMessage $message): void
        {
            $this->handledMessage = $message;
        }
    };

    $worker = new DatabaseOutboxWorker(
        $database,
        [$failureHandler, $successHandler],
        new NullLogger(),
        maxAttempts: 3,
        leaseSeconds: 60,
        baseRetryDelaySeconds: 5,
        maxRetryDelaySeconds: 20,
    );

    Assert::same(2, $worker->runOnce(2));

    $failedRow = $database->table('outbox_message')
        ->where('message_id', $failureId->toString())
        ->fetch();

    Assert::notNull($failedRow);
    Assert::same('pending', $failedRow->status);
    Assert::same(1, (int) $failedRow->attempts);
    Assert::same(RuntimeException::class, $failedRow->last_error);
    Assert::null($failedRow->lock_token);
    Assert::null($failedRow->locked_until);
    Assert::same(1, (int) $database->query(
        'SELECT available_at > TIMESTAMPADD(SECOND, 4, UTC_TIMESTAMP(6)) AND available_at <= TIMESTAMPADD(SECOND, 5, UTC_TIMESTAMP(6)) AS retry_window FROM outbox_message WHERE message_id = ?',
        $failureId->toString(),
    )->fetchField());
    Assert::same($failureId->toString(), $failureHandler->handledMessages[0]->messageId);
    Assert::same(1, $failureHandler->handledMessages[0]->attempt);

    $successRow = $database->table('outbox_message')
        ->where('message_id', $successId->toString())
        ->fetch();

    Assert::notNull($successRow);
    Assert::same('delivered', $successRow->status);
    Assert::same(1, (int) $successRow->attempts);
    Assert::notNull($successRow->delivered_at);
    Assert::null($successRow->lock_token);
    Assert::null($successRow->locked_until);
    Assert::notNull($successHandler->handledMessage);
    Assert::same($successId->toString(), $successHandler->handledMessage->messageId);
    Assert::same(1, $successHandler->handledMessage->attempt);
    Assert::same(['test' => true], $successHandler->handledMessage->payload);

    // Retry je dostupný až po available_at; pro deterministický test jej posuneme do minulosti.
    $database->query(
        'UPDATE outbox_message SET available_at = TIMESTAMPADD(SECOND, -1, UTC_TIMESTAMP(6)) WHERE message_id = ?',
        $failureId->toString(),
    );

    Assert::same(1, $worker->runOnce(1));

    $retriedRow = $database->table('outbox_message')
        ->where('message_id', $failureId->toString())
        ->fetch();

    Assert::notNull($retriedRow);
    Assert::same('pending', $retriedRow->status);
    Assert::same(2, (int) $retriedRow->attempts);
    Assert::true($database->query(
        'SELECT available_at > TIMESTAMPADD(SECOND, 9, UTC_TIMESTAMP(6)) AND available_at <= TIMESTAMPADD(SECOND, 10, UTC_TIMESTAMP(6)) AS retry_window FROM outbox_message WHERE message_id = ?',
        $failureId->toString(),
    )->fetchField() == 1);
    Assert::same($failureId->toString(), $failureHandler->handledMessages[1]->messageId);
    Assert::same(2, $failureHandler->handledMessages[1]->attempt);

    $database->query(
        'UPDATE outbox_message SET available_at = TIMESTAMPADD(SECOND, -1, UTC_TIMESTAMP(6)) WHERE message_id = ?',
        $failureId->toString(),
    );
    Assert::same(1, $worker->runOnce(1));

    $deadRow = $database->table('outbox_message')
        ->where('message_id', $failureId->toString())
        ->fetch();

    Assert::notNull($deadRow);
    Assert::same('dead', $deadRow->status);
    Assert::same(3, (int) $deadRow->attempts);
    Assert::notNull($deadRow->dead_at);
    Assert::null($deadRow->lock_token);
    Assert::null($deadRow->locked_until);
    Assert::same($failureId->toString(), $failureHandler->handledMessages[2]->messageId);
    Assert::same(3, $failureHandler->handledMessages[2]->attempt);

    // Duplicitní handler pro stejný eventName je odmítnut už při sestavení workeru.
    $duplicateHandler = new class implements OutboxMessageHandler {
        public function eventName(): string
        {
            return 'test.outbox.worker.duplicate';
        }

        public function handle(OutboxMessage $message): void {}
    };

    $duplicateRejected = false;

    try {
        new DatabaseOutboxWorker(
            $database,
            [$duplicateHandler, $duplicateHandler],
            new NullLogger(),
        );
    } catch (LogicException) {
        $duplicateRejected = true;
    }

    Assert::true($duplicateRejected);

    // Chybějící handler je bezpečně evidován jako selhání a po limitu přejde do dead.
    $missingHandlerId = $createMessage('test.outbox.worker.missing-handler');
    $ids[] = $missingHandlerId;
    $missingHandlerWorker = new DatabaseOutboxWorker(
        $database,
        [],
        new NullLogger(),
        maxAttempts: 1,
    );

    Assert::same(1, $missingHandlerWorker->runOnce(1));
    $missingHandlerRow = $database->table('outbox_message')
        ->where('message_id', $missingHandlerId->toString())
        ->fetch();

    Assert::notNull($missingHandlerRow);
    Assert::same('dead', $missingHandlerRow->status);
    Assert::same(1, (int) $missingHandlerRow->attempts);
    Assert::same(LogicException::class, $missingHandlerRow->last_error);

    // Expirovaný processing lease lze bezpečně převzít novým workerem.
    $expiredId = $createMessage('test.outbox.worker.expired-lease');
    $ids[] = $expiredId;
    $oldToken = random_bytes(16);

    $database->query(
        "UPDATE outbox_message
        SET status = 'processing',
            attempts = 1,
            lock_token = ?,
            locked_until = TIMESTAMPADD(SECOND, -1, UTC_TIMESTAMP(6))
        WHERE message_id = ?",
        $oldToken,
        $expiredId->toString(),
    );

    $expiredHandler = new class implements OutboxMessageHandler {
        public ?OutboxMessage $handledMessage = null;

        public function eventName(): string
        {
            return 'test.outbox.worker.expired-lease';
        }

        public function handle(OutboxMessage $message): void
        {
            $this->handledMessage = $message;
        }
    };

    $expiredWorker = new DatabaseOutboxWorker(
        $database,
        [$expiredHandler],
        new NullLogger(),
    );

    Assert::same(1, $expiredWorker->runOnce(1));
    Assert::notNull($expiredHandler->handledMessage);
    Assert::same(2, $expiredHandler->handledMessage->attempt);

    $expiredRow = $database->table('outbox_message')
        ->where('message_id', $expiredId->toString())
        ->fetch();

    Assert::notNull($expiredRow);
    Assert::same('delivered', $expiredRow->status);
    Assert::same(2, (int) $expiredRow->attempts);

    // Expirovaný poslední pokus se nesmí znovu doručit; claim jej rovnou uzavře jako dead.
    $expiredFinalId = $createMessage('test.outbox.worker.expired-final-attempt');
    $ids[] = $expiredFinalId;

    $database->query(
        "UPDATE outbox_message
        SET status = 'processing',
            attempts = 3,
            lock_token = ?,
            locked_until = TIMESTAMPADD(SECOND, -1, UTC_TIMESTAMP(6))
        WHERE message_id = ?",
        random_bytes(16),
        $expiredFinalId->toString(),
    );

    $finalAttemptHandler = new class implements OutboxMessageHandler {
        public bool $called = false;

        public function eventName(): string
        {
            return 'test.outbox.worker.expired-final-attempt';
        }

        public function handle(OutboxMessage $message): void
        {
            $this->called = true;
        }
    };

    $finalAttemptWorker = new DatabaseOutboxWorker(
        $database,
        [$finalAttemptHandler],
        new NullLogger(),
        maxAttempts: 3,
    );

    Assert::same(0, $finalAttemptWorker->runOnce(1));
    Assert::false($finalAttemptHandler->called);

    $expiredFinalRow = $database->table('outbox_message')
        ->where('message_id', $expiredFinalId->toString())
        ->fetch();

    Assert::notNull($expiredFinalRow);
    Assert::same('dead', $expiredFinalRow->status);
    Assert::same(3, (int) $expiredFinalRow->attempts);
    Assert::notNull($expiredFinalRow->dead_at);
    Assert::same('Lease expired after maximum attempts.', $expiredFinalRow->last_error);

    // Simulace převzetí jiným workerem: původní token už nesmí potvrdit doručení.
    $staleId = $createMessage('test.outbox.worker.stale-lease');
    $ids[] = $staleId;

    $staleHandler = new class ($database, $staleId->toString()) implements OutboxMessageHandler {
        public function __construct(
            private readonly Explorer $database,
            private readonly string $messageId,
        ) {}

        public function eventName(): string
        {
            return 'test.outbox.worker.stale-lease';
        }

        public function handle(OutboxMessage $message): void
        {
            $this->database->query(
                "UPDATE outbox_message
                SET lock_token = ?,
                    locked_until = TIMESTAMPADD(SECOND, 60, UTC_TIMESTAMP(6))
                WHERE message_id = ?",
                random_bytes(16),
                $this->messageId,
            );
        }
    };

    $staleWorker = new DatabaseOutboxWorker($database, [$staleHandler], new NullLogger());
    Assert::same(1, $staleWorker->runOnce(1));

    $staleRow = $database->table('outbox_message')
        ->where('message_id', $staleId->toString())
        ->fetch();

    Assert::notNull($staleRow);
    Assert::same('processing', $staleRow->status);
    Assert::notNull($staleRow->lock_token);
    Assert::notNull($staleRow->locked_until);
    Assert::null($staleRow->delivered_at);
} finally {
    foreach ($ids as $id) {
        $database->table('outbox_message')
            ->where('message_id', $id->toString())
            ->delete();
    }
}

echo "OK\n";
?>
--EXPECT--
OK