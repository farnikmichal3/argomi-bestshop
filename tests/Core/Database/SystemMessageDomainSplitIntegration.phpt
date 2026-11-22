--TEST--
System messages are split by owning domain without losing data or locale constraints
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

$tables = $pdo->query(
    "SELECT TABLE_NAME
       FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_TYPE = 'BASE TABLE'
        AND TABLE_NAME IN (
            'translation_message',
            'catalog_message_translation',
            'cart_message_translation',
            'checkout_message_translation'
        )
      ORDER BY TABLE_NAME",
)->fetchAll(PDO::FETCH_COLUMN);

Assert::same(
    [
        'cart_message_translation',
        'catalog_message_translation',
        'checkout_message_translation',
    ],
    $tables,
    'The central table must be replaced by exactly the three domain-owned tables.',
);

$expectedCounts = [
    'catalog_message_translation' => 52,
    'cart_message_translation' => 4,
    'checkout_message_translation' => 6,
];

foreach ($expectedCounts as $table => $expectedCount) {
    $count = (int) $pdo->query(sprintf('SELECT COUNT(*) FROM %s', $table))->fetchColumn();
    Assert::same($expectedCount, $count, sprintf('Unexpected row count in %s.', $table));

    $columns = $pdo->query(
        'SELECT COLUMN_NAME
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ' . $pdo->quote($table) . '
          ORDER BY ORDINAL_POSITION',
    )->fetchAll(PDO::FETCH_COLUMN);

    Assert::same(
        [
            'id',
            'message_key',
            'locale',
            'message_text',
            'source',
            'is_customized',
            'default_hash',
            'is_outdated',
            'updated_at',
            'updated_by',
        ],
        $columns,
        sprintf('Unexpected columns in %s.', $table),
    );

    $uniqueIndex = $pdo->prepare(
        "SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns
           FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND NON_UNIQUE = 0
          GROUP BY INDEX_NAME
         HAVING indexed_columns = 'locale,message_key'",
    );
    $uniqueIndex->execute([$table]);
    Assert::same(
        1,
        count($uniqueIndex->fetchAll(PDO::FETCH_ASSOC)),
        sprintf('%s must enforce uniqueness of (locale, message_key).', $table),
    );

    $localeForeignKey = $pdo->prepare(
        "SELECT k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE
           FROM information_schema.KEY_COLUMN_USAGE k
           INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r
             ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
            AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
            AND r.TABLE_NAME = k.TABLE_NAME
          WHERE k.TABLE_SCHEMA = DATABASE()
            AND k.TABLE_NAME = ?
            AND k.COLUMN_NAME = 'locale'
            AND k.REFERENCED_TABLE_NAME IS NOT NULL",
    );
    $localeForeignKey->execute([$table]);

    Assert::same(
        [
            'REFERENCED_TABLE_NAME' => 'language',
            'REFERENCED_COLUMN_NAME' => 'code',
            'DELETE_RULE' => 'RESTRICT',
        ],
        $localeForeignKey->fetch(PDO::FETCH_ASSOC),
        sprintf('%s must preserve its language(code) RESTRICT foreign key.', $table),
    );
}

// Klíče i obě jazykové hodnoty musí být rozděleny do správných vlastníků.
$expectedKeys = [
    'catalog_message_translation' => [
        'button.add_to_cart',
        'button.view_details',
        'stock.in_stock',
        'stock.out_of_stock',
        'categories.title',
        'categories.column.id',
        'categories.column.code',
        'categories.column.name',
        'categories.column.status',
        'categories.status.active',
        'categories.status.inactive',
        'categories.empty',
        'categories.form.title',
        'categories.form.parent',
        'categories.form.parent.none',
        'categories.form.code',
        'categories.form.sort_order',
        'categories.form.active',
        'categories.form.name',
        'categories.form.description',
        'categories.form.submit',
        'categories.form.created',
        'categories.form.error.required',
        'categories.form.error.sort_order',
        'categories.form.error.invalid',
        'categories.form.error.duplicate',
    ],
    'cart_message_translation' => [
        'empty.message',
        'empty.title',
    ],
    'checkout_message_translation' => [
        'button.place_order',
        'confirmation.message',
        'confirmation.title',
    ],
];

foreach ($expectedKeys as $table => $keys) {
    $statement = $pdo->query(sprintf(
        'SELECT message_key, locale, message_text, source, is_customized, default_hash, is_outdated
           FROM %s
          ORDER BY message_key, locale',
        $table,
    ));
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    Assert::same(count($keys) * 2, count($rows), sprintf('%s must contain cs and en for every key.', $table));

    $actualKeys = array_values(array_unique(array_column($rows, 'message_key')));
    sort($actualKeys);
    sort($keys);
    Assert::same($keys, $actualKeys, sprintf('Unexpected message keys in %s.', $table));

    foreach ($rows as $row) {
        Assert::true(in_array($row['locale'], ['cs', 'en'], true));
        Assert::same('core', $row['source']);
        Assert::same(0, (int) $row['is_customized']);
        Assert::same(0, (int) $row['is_outdated']);
        Assert::true(trim((string) $row['message_text']) !== '');
    }
}

// Tento klíč historicky nemá default_hash; převod jej nesmí dopočítat.
$nullHashes = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM catalog_message_translation
      WHERE message_key = 'button.add_to_cart'
        AND default_hash IS NULL",
)->fetchColumn();
Assert::same(2, $nullHashes, 'Both add_to_cart locale rows must preserve their NULL default_hash.');

$duplicateRows = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM (
           SELECT locale, message_key
             FROM catalog_message_translation
            GROUP BY locale, message_key
           HAVING COUNT(*) > 1
           UNION ALL
           SELECT locale, message_key
             FROM cart_message_translation
            GROUP BY locale, message_key
           HAVING COUNT(*) > 1
           UNION ALL
           SELECT locale, message_key
             FROM checkout_message_translation
            GROUP BY locale, message_key
           HAVING COUNT(*) > 1
       ) duplicates",
)->fetchColumn();
Assert::same(0, $duplicateRows, 'A domain table must not contain duplicate locale/message keys.');

echo "OK\n";
?>
--EXPECT--
OK
