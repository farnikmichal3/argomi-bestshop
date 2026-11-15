--TEST--
Deleting a variant axis or enum value cannot silently remove variant attribute values
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
$productCode = 'f3-24-test-' . $token;
$variantSku = 'F3-24-' . strtoupper($token);

$pdo->beginTransaction();

try {
    $insertProduct = $pdo->prepare(
        'INSERT INTO product (code, tax_class_id) VALUES (?, ?)',
    );
    $insertProduct->execute([$productCode, $taxClassId]);
    $productId = (int) $pdo->lastInsertId();

    $insertAttribute = $pdo->prepare(
        'INSERT INTO attribute (code, type) VALUES (?, \'enum\')',
    );
    $insertAxis = $pdo->prepare(
        'INSERT INTO product_variant_axis (product_id, attribute_id) VALUES (?, ?)',
    );
    $insertValue = $pdo->prepare(
        'INSERT INTO attribute_value (attribute_id, code) VALUES (?, ?)',
    );

    $attributeIds = [];

    foreach (['axis', 'value'] as $suffix) {
        $insertAttribute->execute(['f3_24_' . $suffix . '_' . $token]);
        $attributeId = (int) $pdo->lastInsertId();
        $attributeIds[$suffix] = $attributeId;

        $insertAxis->execute([$productId, $attributeId]);
        $insertValue->execute([$attributeId, 'option_' . $suffix]);
        $attributeIds[$suffix . 'Value'] = (int) $pdo->lastInsertId();
    }

    $insertVariant = $pdo->prepare(
        'INSERT INTO product_variant (product_id, sku, combination_hash) VALUES (?, ?, ?)',
    );
    $insertVariant->execute([
        $productId,
        $variantSku,
        hash('sha256', 'f3-24-' . $token),
    ]);
    $variantId = (int) $pdo->lastInsertId();

    $insertVariantValue = $pdo->prepare(
        'INSERT INTO product_variant_attribute_value
            (product_variant_id, product_id, attribute_id, attribute_value_id)
         VALUES (?, ?, ?, ?)',
    );

    foreach (['axis', 'value'] as $suffix) {
        $insertVariantValue->execute([
            $variantId,
            $productId,
            $attributeIds[$suffix],
            $attributeIds[$suffix . 'Value'],
        ]);
    }

    $deleteAxisError = null;

    try {
        $deleteAxis = $pdo->prepare(
            'DELETE FROM product_variant_axis WHERE product_id = ? AND attribute_id = ?',
        );
        $deleteAxis->execute([$productId, $attributeIds['axis']]);
    } catch (\PDOException $exception) {
        $deleteAxisError = $exception;
    }

    Assert::type(
        \PDOException::class,
        $deleteAxisError,
        'Deleting an axis used by a variant must be rejected.',
    );
    Assert::same('23000', $deleteAxisError->getCode());

    $deleteValueError = null;

    try {
        $deleteValue = $pdo->prepare(
            'DELETE FROM attribute_value WHERE id = ? AND attribute_id = ?',
        );
        $deleteValue->execute([$attributeIds['valueValue'], $attributeIds['value']]);
    } catch (\PDOException $exception) {
        $deleteValueError = $exception;
    }

    Assert::type(
        \PDOException::class,
        $deleteValueError,
        'Deleting an enum value used by a variant must be rejected.',
    );
    Assert::same('23000', $deleteValueError->getCode());

    $remainingAssignments = $pdo->prepare(
        'SELECT COUNT(*) FROM product_variant_attribute_value
         WHERE product_variant_id = ? AND product_id = ?',
    );
    $remainingAssignments->execute([$variantId, $productId]);

    Assert::same(
        2,
        (int) $remainingAssignments->fetchColumn(),
        'Both variant attribute assignments must remain after rejected deletes.',
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
