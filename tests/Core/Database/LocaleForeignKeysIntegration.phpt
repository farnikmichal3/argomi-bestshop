--TEST--
Every locale references an existing language with the correct delete rule
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

$rows = $pdo->query(
    "SELECT
         k.TABLE_NAME,
         k.REFERENCED_TABLE_NAME,
         k.REFERENCED_COLUMN_NAME,
         r.DELETE_RULE
     FROM information_schema.KEY_COLUMN_USAGE k
     INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r
         ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
        AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
        AND r.TABLE_NAME = k.TABLE_NAME
     WHERE k.TABLE_SCHEMA = DATABASE()
       AND k.COLUMN_NAME = 'locale'
       AND k.REFERENCED_TABLE_NAME IS NOT NULL
     ORDER BY k.TABLE_NAME",
)->fetchAll(PDO::FETCH_ASSOC);

$actual = [];

foreach ($rows as $row) {
    $actual[$row['TABLE_NAME']] = [
        $row['REFERENCED_TABLE_NAME'],
        $row['REFERENCED_COLUMN_NAME'],
        strtoupper($row['DELETE_RULE']),
    ];
}

$expected = [
    'attribute_translation' => ['language', 'code', 'RESTRICT'],
    'attribute_value_translation' => ['language', 'code', 'RESTRICT'],
    'cart' => ['language', 'code', 'RESTRICT'],
    'category_translation' => ['language', 'code', 'RESTRICT'],
    'customer' => ['language', 'code', 'SET NULL'],
    'payment_method_translation' => ['language', 'code', 'RESTRICT'],
    'product_option_translation' => ['language', 'code', 'RESTRICT'],
    'product_option_value_translation' => ['language', 'code', 'RESTRICT'],
    'product_translation' => ['language', 'code', 'RESTRICT'],
    'shipping_method_translation' => ['language', 'code', 'RESTRICT'],
    'shop_order' => ['language', 'code', 'RESTRICT'],
    'cart_message_translation' => ['language', 'code', 'RESTRICT'],
    'catalog_message_translation' => ['language', 'code', 'RESTRICT'],
    'checkout_message_translation' => ['language', 'code', 'RESTRICT'],
    'unit_translation' => ['language', 'code', 'RESTRICT'],
];

Assert::same($expected, $actual, 'Every locale column must reference language(code) with its approved delete rule.');

$token = bin2hex(random_bytes(8));
$languageCode = 'z' . substr($token, 0, 9);
$publicId = bin2hex(random_bytes(13));
$email = 'f3-26-' . $token . '@example.invalid';

$pdo->beginTransaction();

try {
    $insertLanguage = $pdo->prepare(
        'INSERT INTO language (code, name) VALUES (?, ?)',
    );
    $insertLanguage->execute([$languageCode, 'F3-26 test']);

    $insertCustomer = $pdo->prepare(
        'INSERT INTO customer (public_id, email, locale) VALUES (?, ?, ?)',
    );
    $insertCustomer->execute([$publicId, $email, $languageCode]);

    $insertMessage = $pdo->prepare(
        'INSERT INTO catalog_message_translation (message_key, locale, message_text)
         VALUES (?, ?, ?)',
    );
    $insertMessage->execute([$token, $languageCode, 'Test']);

    $deleteLanguageError = null;

    try {
        $deleteLanguage = $pdo->prepare('DELETE FROM language WHERE code = ?');
        $deleteLanguage->execute([$languageCode]);
    } catch (PDOException $exception) {
        $deleteLanguageError = $exception;
    }

    Assert::true(
        $deleteLanguageError instanceof PDOException,
        'A language used by a required translation must not be deleted.',
    );
    Assert::same('23000', (string) $deleteLanguageError->getCode());

    $deleteMessage = $pdo->prepare(
        'DELETE FROM catalog_message_translation WHERE message_key = ? AND locale = ?',
    );
    $deleteMessage->execute([$token, $languageCode]);

    $deleteLanguage = $pdo->prepare('DELETE FROM language WHERE code = ?');
    $deleteLanguage->execute([$languageCode]);

    $customerLocale = $pdo->prepare(
        'SELECT locale FROM customer WHERE public_id = ?',
    );
    $customerLocale->execute([$publicId]);

    Assert::same(
        null,
        $customerLocale->fetchColumn(),
        'Deleting an otherwise unused language must clear the optional customer locale.',
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
