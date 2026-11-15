--TEST--
Transactional Outbox persists atomically through the shared MySQL transaction
--FILE--
<?php

use Tests\\Support\\IntegrationTestBootstrap;

use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Clock\Clock;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Infrastructure\Clock\FrozenClock;
use App\Shared\Infrastructure\Outbox\DatabaseOutboxWriter;
use App\Shared\Infrastructure\PublicId\SymfonyUlidGenerator;
use App\Bootstrap;
use Nette\Database\Explorer;
use Tester\Assert;
use Tester\Environment;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;

/** @var Transaction $transaction */
$transaction = $container->getByType(Transaction::class);

/** @var OutboxWriter $registeredWriter */
$registeredWriter = $container->getByType(OutboxWriter::class);
Assert::type(OutboxWriter::class, $registeredWriter);

$clock = new FrozenClock(
    new DateTimeImmutable('2026-11-15 12:34:56.123456', new DateTimeZone('Europe/Berlin')),
);
$writer = new DatabaseOutboxWriter($database, $clock, new SymfonyUlidGenerator());

Assert::type(Clock::class, $clock);
Assert::same('UTC', $clock->now()->getTimezone()->getName());

Assert::exception(
    static fn () => $writer->record('test.outbox.outside-transaction', []),
    LogicException::class,
);

$committedId = null;

try {
    $committedId = $transaction->run(
        static fn (): PublicId => $writer->record(
            'test.outbox.committed',
            ['orderId' => 123, 'nested' => ['value' => 'ok']],
        ),
    );

    Assert::type(PublicId::class, $committedId);
    Assert::same(26, strlen($committedId->toString()));

    $committedRow = $database->table('outbox_message')
        ->where('message_id', $committedId->toString())
        ->fetch();

    Assert::notNull($committedRow);
    Assert::same('test.outbox.committed', $committedRow->event_name);
    Assert::same('pending', $committedRow->status);

    $payload = json_decode($committedRow->payload, false, 512, JSON_THROW_ON_ERROR);
    Assert::type(stdClass::class, $payload);
    Assert::same(123, $payload->orderId);
    Assert::same('ok', $payload->nested->value);

    Assert::same(
        '2026-11-15 11:34:56.123456',
        $committedRow->created_at->format('Y-m-d H:i:s.u'),
    );

    Assert::same(
        '2026-11-15 11:34:56.123456',
        $committedRow->available_at->format('Y-m-d H:i:s.u'),
    );

    $rolledBackId = null;

    Assert::exception(
        static function () use ($transaction, $writer, &$rolledBackId): void {
            $transaction->run(
                static function () use ($writer, &$rolledBackId): void {
                    $rolledBackId = $writer->record('test.outbox.rolled-back', ['orderId' => 456]);
                    throw new RuntimeException('Force transaction rollback.');
                },
            );
        },
        RuntimeException::class,
    );

    Assert::type(PublicId::class, $rolledBackId);
    Assert::null(
        $database->table('outbox_message')
            ->where('message_id', $rolledBackId->toString())
            ->fetch(),
    );
} finally {
    if ($committedId instanceof PublicId) {
        $database->table('outbox_message')
            ->where('message_id', $committedId->toString())
            ->delete();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
