--TEST--
System message SQL seeds are idempotent and never overwrite existing rows in owning domain tables
--FILE--
<?php

use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

$token = bin2hex(random_bytes(8));

$pdo->beginTransaction();

try {
    $domains = [
        'catalog_message_translation',
        'cart_message_translation',
        'checkout_message_translation,
    ];

    foreach ($domains as $index => $table) {
        $seedKey = 'f4_13.seed.' . $token . '.' . $index;
        $customKey = 'f4_13.custom.' . $token . '.' . $index;

        $seed = $pdo->prepare(sprintf(
            "INSERT INTO %s (message_key, locale, message_text, source, is_customized, is_outdated) "
            . "VALUES (?, 'cs', ?, 'core', 0, 0) "
            . 'ON DUPLICATE KEY UPDATE id = id',
            $table,
        ));

        $seed->execute([$seedKey, 'Výchozí text']);
        $seed->execute([$seedKey, 'Nové výchozí znění']);

        $seeded = $pdo->prepare(sprintf(
            'SELECT message_text, source, is_customized, default_hash, is_outdated '
            . 'FROM %s WHERE locale = ? AND message_key = ?',
            $table,
        ));
        $seeded->execute(['cs', $seedKey]);

        Assert::same([
            'message_text' => 'Výchozí text',
            'source' => 'core',
            'is_customized' => 0,
            'default_hash' => null,
            'is_outdated' => 0,
        ], $seeded->fetch(PDO::FETCH_ASSOC));

        $insertCustom = $pdo->prepare(sprintf(
            "INSERT INTO %s (message_key, locale, message_text, source, is_customized) "
            . "VALUES (?, 'cs', ?, 'custom', 1)",
            $table,
        ));
        $insertCustom->execute([$customKey, 'Vlastní text']);

        $seed->execute([$customKey, 'Výchozí text']);

        $custom = $pdo->prepare(sprintf(
            'SELECT message_text, source, is_customized '
            . 'FROM %s WHERE locale = ? AND message_key = ?',
            $table,
        ));
        $custom->execute(['cs', $customKey]);

        Assert::same([
            'message_text' => 'Vlastní text',
            'source' => 'custom',
            'is_customized' => 1,
        ], $custom->fetch(PDO::FETCH_ASSOC));
    }
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
