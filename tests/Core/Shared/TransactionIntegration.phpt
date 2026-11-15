<?php

declare(strict_types=1);

use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Infrastructure\Transaction\NetteTransaction;
use App\Bootstrap;
use Nette\Database\Explorer;
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
    throw new RuntimeException('Transaction integration tests may run only against argomi_bestshop_test using argomi_test_app.');
}

/** @var Transaction $transaction */
$transaction = $container->getByType(Transaction::class);
Assert::type(NetteTransaction::class, $transaction);
Assert::same($transaction, $container->getByType(NetteTransaction::class));

/** @var OutboxWriter $writer */
$writer = $container->getByType(OutboxWriter::class);

/** @var list<PublicId> $createdIds */
$createdIds = [];

/**
 * Zapíše značkovací Outbox záznam a zapamatuje jeho ID pro bezpečný úklid.
 *
 * @param array<string, mixed> $payload
 */
$record = static function (string $eventName, array $payload = []) use ($writer, &$createdIds): PublicId {
    $id = $writer->record($eventName, $payload);
    $createdIds[] = $id;

    return $id;
};

try {
    // Úspěšná nejvyšší transakce potvrdí zápis a vrátí výsledek callbacku.
    $committedId = null;
    $result = $transaction->run(
        static function () use ($record, &$committedId): string {
            $committedId = $record('test.transaction.commit', ['case' => 'commit']);

            return 'committed-result';
        },
    );

    Assert::same('committed-result', $result);
    Assert::type(PublicId::class, $committedId);
    Assert::notNull(
        $database->table('outbox_message')
            ->where('message_id', $committedId->toString())
            ->fetch(),
    );

    // Výjimka opouštějící nejvyšší callback musí být propagována a změny vráceny zpět.
    $exceptionId = null;
    Assert::exception(
        static function () use ($transaction, $record, &$exceptionId): void {
            $transaction->run(
                static function () use ($record, &$exceptionId): void {
                    $exceptionId = $record('test.transaction.exception', ['case' => 'exception']);
                    throw new RuntimeException('Expected transaction exception.');
                },
            );
        },
        RuntimeException::class,
        'Expected transaction exception.',
    );

    Assert::type(PublicId::class, $exceptionId);
    Assert::null(
        $database->table('outbox_message')
            ->where('message_id', $exceptionId->toString())
            ->fetch(),
    );

    // PHP Error se musí chovat stejně jako Exception: rollback a beze změny propagace.
    $errorId = null;
    Assert::exception(
        static function () use ($transaction, $record, &$errorId): void {
            $transaction->run(
                static function () use ($record, &$errorId): void {
                    $errorId = $record('test.transaction.error', ['case' => 'error']);
                    throw new Error('Expected transaction error.');
                },
            );
        },
        Error::class,
        'Expected transaction error.',
    );

    Assert::type(PublicId::class, $errorId);
    Assert::null(
        $database->table('outbox_message')
            ->where('message_id', $errorId->toString())
            ->fetch(),
    );

    // Zachycená chyba vnořeného volání neprovádí vlastní rollback ani savepoint.
    // Vnější transakce proto potvrdí zápisy z obou úrovní.
    $outerBeforeCaughtId = null;
    $innerCaughtId = null;
    $outerAfterCaughtId = null;

    $nestedResult = $transaction->run(
        static function () use (
            $transaction,
            $record,
            &$outerBeforeCaughtId,
            &$innerCaughtId,
            &$outerAfterCaughtId,
        ): string {
            $outerBeforeCaughtId = $record('test.transaction.nested-caught.outer-before');

            try {
                $transaction->run(
                    static function () use ($record, &$innerCaughtId): void {
                        $innerCaughtId = $record('test.transaction.nested-caught.inner');
                        throw new RuntimeException('Caught nested transaction exception.');
                    },
                );
            } catch (RuntimeException $exception) {
                Assert::same('Caught nested transaction exception.', $exception->getMessage());
            }

            $outerAfterCaughtId = $record('test.transaction.nested-caught.outer-after');

            return 'outer-committed';
        },
    );

    Assert::same('outer-committed', $nestedResult);

    foreach ([$outerBeforeCaughtId, $innerCaughtId, $outerAfterCaughtId] as $id) {
        Assert::type(PublicId::class, $id);
        Assert::notNull(
            $database->table('outbox_message')
                ->where('message_id', $id->toString())
                ->fetch(),
        );
    }

    // Nezachycená chyba z vnoření opustí nejvyšší callback a vrátí zpět celou transakci.
    $outerRollbackId = null;
    $innerRollbackId = null;

    Assert::exception(
        static function () use ($transaction, $record, &$outerRollbackId, &$innerRollbackId): void {
            $transaction->run(
                static function () use ($transaction, $record, &$outerRollbackId, &$innerRollbackId): void {
                    $outerRollbackId = $record('test.transaction.nested-rollback.outer');

                    $transaction->run(
                        static function () use ($record, &$innerRollbackId): void {
                            $innerRollbackId = $record('test.transaction.nested-rollback.inner');
                            throw new RuntimeException('Uncaught nested transaction exception.');
                        },
                    );
                },
            );
        },
        RuntimeException::class,
        'Uncaught nested transaction exception.',
    );

    foreach ([$outerRollbackId, $innerRollbackId] as $id) {
        Assert::type(PublicId::class, $id);
        Assert::null(
            $database->table('outbox_message')
                ->where('message_id', $id->toString())
                ->fetch(),
        );
    }
} finally {
    // Úklid probíhá až po ověření výsledků; odstraní pouze řádky vytvořené tímto testem.
    foreach ($createdIds as $id) {
        $database->table('outbox_message')
            ->where('message_id', $id->toString())
            ->delete();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
