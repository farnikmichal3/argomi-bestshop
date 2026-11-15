--TEST--
Transactional Outbox worker claims, delivers, retries and protects lease ownership
--FILE--
<?php

use App\Shared\Application\Outbox\OutboxMessage;
use App\Shared\Application\Outbox\OutboxMessageHandler;
use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Infrastructure\Outbox\DatabaseOutboxWorker;
use App\Bootstrap;
use Nette\Database\Explorer;
use Psr\Log\NullLogger;
use Tester\Assert;
use Tester\Environment;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';

if (!is_file($rootDir . '/config/local.testing.neon')) {
    Environment::skip('Create config/local.testing.neon to run MySQL integration tests.');
}

$container = (new Bootstrap('local.testing.neon'))->bootWebApplication();

/** @var Explorer $database */
$database = $container->getByType(Explorer::class);

$identity = $database->getConnection()->getPdo()
    ->query('SELECT DATABASE() AS database_name, CURRENT_USER() AS database_user')
    ->fetch(PDO::FETCH_ASSOC);

if (
    !is_array($identity)
    || ($identity['database_name'] ?? null) !== 'argomi_bestshop_test'
    || !in_array($identity['database_user'] ?? null, [
        'argomi_test_app@localhost',
        'argomi_test_app@127.0.0.1',
    ], true)
) {
    throw new RuntimeException('Outbox worker integration tests may run only against argomi_bestshop_test using argomi_test_app.');
}

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
        public function eventName(): string
        {
            return 'test.outbox.worker.failure';
        }

        public function handle(OutboxMessage $message): void
        {
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
        maxAttempts: 2,
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

    $deadRow = $database->table('outbox_message')
        ->where('message_id', $failureId->toString())
        ->fetch();

    Assert::notNull($deadRow);
    Assert::same('dead', $deadRow->status);
    Assert::same(2, (int) $deadRow->attempts);
    Assert::notNull($deadRow->dead_at);
    Assert::null($deadRow->lock_token);
    Assert::null($deadRow->locked_until);

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