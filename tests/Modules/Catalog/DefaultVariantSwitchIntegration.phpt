--TEST--
Switching a product's default variant is atomic and protected by the unique generated key
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

$taxClassId = $pdo->query(
    "SELECT id FROM tax_class WHERE code = 'STANDARD'",
)->fetchColumn();

Assert::notSame(false, $taxClassId, 'The STANDARD tax class fixture must exist.');

$token = bin2hex(random_bytes(8));
$productCode = 'f3-29-test-' . $token;
$variantSkus = [
    'F3-29-A-' . strtoupper($token),
    'F3-29-B-' . strtoupper($token),
    'F3-29-C-' . strtoupper($token),
];

$pdo->beginTransaction();

try {
    $insertProduct = $pdo->prepare(
        'INSERT INTO product (code, tax_class_id) VALUES (?, ?)',
    );
    $insertProduct->execute([$productCode, $taxClassId]);
    $productId = (int) $pdo->lastInsertId();

    $insertVariant = $pdo->prepare(
        'INSERT INTO product_variant (product_id, sku, combination_hash) VALUES (?, ?, ?)',
    );

    $variantIds = [];

    foreach ($variantSkus as $index => $sku) {
        $insertVariant->execute([
            $productId,
            $sku,
            hash('sha256', 'f3-29-' . $token . '-' . $index),
        ]);
        $variantIds[] = (int) $pdo->lastInsertId();
    }

    [$firstVariantId, $secondVariantId, $thirdVariantId] = $variantIds;

    // Databáze musí mít unikátní index nad generovaným příznakem výchozí varianty.
    $defaultIndex = $pdo->query(
        "SELECT COUNT(*)
           FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'product_variant'
            AND INDEX_NAME = 'uq_product_variant_single_default'",
    )->fetchColumn();

    Assert::same(1, (int) $defaultIndex);

    $defaultColumn = $pdo->query(
        "SELECT EXTRA
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'product_variant'
            AND COLUMN_NAME = 'default_flag'",
    )->fetchColumn();

    Assert::contains('STORED GENERATED', (string) $defaultColumn);

    $setDefault = $pdo->prepare(
        'UPDATE product_variant SET is_default = ? WHERE id = ? AND product_id = ?',
    );

    $setDefault->execute([1, $firstVariantId, $productId]);

    Assert::same(
        1,
        (int) $pdo->query(
            'SELECT COUNT(*) FROM product_variant WHERE product_id = ' . $productId . ' AND is_default = 1',
        )->fetchColumn(),
    );

    // Unikátní klíč odmítne druhou výchozí variantu, pokud první nebyla uvolněna.
    $duplicateDefaultError = null;

    try {
        $setDefault->execute([1, $secondVariantId, $productId]);
    } catch (PDOException $exception) {
        $duplicateDefaultError = $exception;
    }

    if (!$duplicateDefaultError instanceof PDOException) {
        throw new RuntimeException('The unique default-variant key must reject a second default variant.');
    }

    Assert::same('23000', (string) $duplicateDefaultError->getCode());

    // Neúspěšný pokus nesmí poškodit již nastavenou výchozí variantu.
    Assert::same(
        1,
        (int) $pdo->query(
            'SELECT is_default FROM product_variant WHERE id = ' . $firstVariantId,
        )->fetchColumn(),
    );

    // Ověříme, že rollback vrátí celý přechod na původní výchozí variantu.
    $pdo->exec('SAVEPOINT before_default_switch');

    $setDefault->execute([0, $firstVariantId, $productId]);
    $setDefault->execute([1, $secondVariantId, $productId]);

    Assert::same(
        $secondVariantId,
        (int) $pdo->query(
            'SELECT id FROM product_variant WHERE product_id = ' . $productId . ' AND is_default = 1',
        )->fetchColumn(),
    );

    $pdo->exec('ROLLBACK TO SAVEPOINT before_default_switch');

    Assert::same(
        $firstVariantId,
        (int) $pdo->query(
            'SELECT id FROM product_variant WHERE product_id = ' . $productId . ' AND is_default = 1',
        )->fetchColumn(),
    );

    // Správné přepnutí proběhne v jedné transakci: nejprve uvolnit, potom nastavit.
    $setDefault->execute([0, $firstVariantId, $productId]);
    $setDefault->execute([1, $secondVariantId, $productId]);

    Assert::same(
        1,
        (int) $pdo->query(
            'SELECT COUNT(*) FROM product_variant WHERE product_id = ' . $productId . ' AND is_default = 1',
        )->fetchColumn(),
    );

    Assert::same(
        $secondVariantId,
        (int) $pdo->query(
            'SELECT id FROM product_variant WHERE product_id = ' . $productId . ' AND is_default = 1',
        )->fetchColumn(),
    );

    Assert::same(
        0,
        (int) $pdo->query(
            'SELECT is_default FROM product_variant WHERE id = ' . $thirdVariantId,
        )->fetchColumn(),
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\\n";
?>
--EXPECT--
OK
