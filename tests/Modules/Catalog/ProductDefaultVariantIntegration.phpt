--TEST--
A product can switch its single default variant transactionally
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

    $insertVariant->execute([
        $productId,
        'F3-29-A-' . strtoupper($token),
        hash('sha256', 'f3-29-a-' . $token),
    ]);
    $firstVariantId = (int) $pdo->lastInsertId();

    $insertVariant->execute([
        $productId,
        'F3-29-B-' . strtoupper($token),
        hash('sha256', 'f3-29-b-' . $token),
    ]);
    $secondVariantId = (int) $pdo->lastInsertId();

    $setDefault = $pdo->prepare(
        'UPDATE product_variant
         SET is_default = ?
         WHERE id = ? AND product_id = ?',
    );

    $setDefault->execute([1, $firstVariantId, $productId]);

    $assertDefault = static function (int $expectedVariantId) use (
        $pdo,
        $productId,
    ): void {
        $statement = $pdo->prepare(
            'SELECT id
             FROM product_variant
             WHERE product_id = ? AND is_default = 1',
        );
        $statement->execute([$productId]);

        Assert::same(
            $expectedVariantId,
            (int) $statement->fetchColumn(),
            'The product must have exactly the expected default variant.',
        );

        $count = $pdo->prepare(
            'SELECT COUNT(*)
             FROM product_variant
             WHERE product_id = ? AND is_default = 1',
        );
        $count->execute([$productId]);

        Assert::same(1, (int) $count->fetchColumn());
    };

    $assertDefault($firstVariantId);

    // Unikátní index musí odmítnout druhou výchozí variantu bez předchozího přepnutí.
    $duplicateDefaultError = null;

    try {
        $setDefault->execute([1, $secondVariantId, $productId]);
    } catch (PDOException $exception) {
        $duplicateDefaultError = $exception;
    }

    if (!$duplicateDefaultError instanceof PDOException) {
        throw new RuntimeException(
            'A product must not have two default variants at the same time.',
        );
    }

    Assert::same('23000', (string) $duplicateDefaultError->getCode());
    $assertDefault($firstVariantId);

    // Neúspěšné/vrácené přepnutí musí obnovit původní výchozí variantu.
    $pdo->exec('SAVEPOINT f3_29_switch_rollback');

    $setDefault->execute([0, $firstVariantId, $productId]);
    $setDefault->execute([1, $secondVariantId, $productId]);

    $assertDefault($secondVariantId);

    $pdo->exec('ROLLBACK TO SAVEPOINT f3_29_switch_rollback');
    $assertDefault($firstVariantId);

    $pdo->exec('RELEASE SAVEPOINT f3_29_switch_rollback');

    // Úspěšné přepnutí proběhne ve stejné transakci a v bezpečném pořadí.
    $setDefault->execute([0, $firstVariantId, $productId]);
    $setDefault->execute([1, $secondVariantId, $productId]);

    $assertDefault($secondVariantId);

    // Ověření fyzické definice unikátního indexu a generovaného sloupce.
    $index = $pdo->query(
        "SELECT NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'product_variant'
           AND INDEX_NAME = 'uq_product_variant_single_default'
         GROUP BY NON_UNIQUE",
    )->fetch(PDO::FETCH_ASSOC);

    if (!is_array($index)) {
        throw new RuntimeException('The unique default-variant index must exist.');
    }

    Assert::same(0, (int) $index['NON_UNIQUE']);
    Assert::same('product_id,default_flag', $index['columns_list']);

    $generatedExpression = $pdo->query(
        "SELECT GENERATION_EXPRESSION
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'product_variant'
           AND COLUMN_NAME = 'default_flag'",
    )->fetchColumn();

    $normalizedExpression = strtolower(str_replace(
        ['`', ' '],
        '',
        (string) $generatedExpression,
    ));

    Assert::same('if((is_default=1),1,null)', $normalizedExpression);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
