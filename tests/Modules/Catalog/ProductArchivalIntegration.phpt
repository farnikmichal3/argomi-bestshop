--TEST--
Products and variants are archived or deactivated instead of physically deleted
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
$productCode = 'f3-28-test-' . $token;
$emptyProductCode = 'f3-28-empty-' . $token;
$variantSku = 'F3-28-' . strtoupper($token);

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
        $variantSku,
        hash('sha256', 'f3-28-' . $token),
    ]);
    $variantId = (int) $pdo->lastInsertId();

    // Archivace produktu musí automaticky deaktivovat všechny jeho aktivní varianty.
    $pdo->prepare("UPDATE product SET status = 'archived' WHERE id = ?")
        ->execute([$productId]);

    Assert::same(
        0,
        (int) $pdo->query(
            'SELECT is_active FROM product_variant WHERE id = ' . $variantId,
        )->fetchColumn(),
    );

    // Přímá aktivace varianty archivovaného produktu není povolena.
    $activationError = null;

    try {
        $pdo->prepare('UPDATE product_variant SET is_active = 1 WHERE id = ?')
            ->execute([$variantId]);
    } catch (PDOException $exception) {
        $activationError = $exception;
    }

    Assert::type(PDOException::class, $activationError);
    Assert::same('45000', (string) $activationError->getCode());

    // Archivovaný produkt nesmí dostat novou aktivní variantu.
    $insertArchivedVariantError = null;

    try {
        $insertVariant->execute([
            $productId,
            'F3-28-NEW-' . strtoupper($token),
            hash('sha256', 'f3-28-new-' . $token),
        ]);
    } catch (PDOException $exception) {
        $insertArchivedVariantError = $exception;
    }

    Assert::type(PDOException::class, $insertArchivedVariantError);
    Assert::same('45000', (string) $insertArchivedVariantError->getCode());

    // Fyzické smazání varianty je zakázáno i bez dalších referencí.
    $deleteVariantError = null;

    try {
        $pdo->prepare('DELETE FROM product_variant WHERE id = ?')
            ->execute([$variantId]);
    } catch (PDOException $exception) {
        $deleteVariantError = $exception;
    }

    Assert::type(PDOException::class, $deleteVariantError);
    Assert::same('45000', (string) $deleteVariantError->getCode());

    // Fyzické smazání produktu je zakázáno i v případě bez variant.
    $insertProduct->execute([$emptyProductCode, $taxClassId]);
    $emptyProductId = (int) $pdo->lastInsertId();

    $deleteEmptyProductError = null;

    try {
        $pdo->prepare('DELETE FROM product WHERE id = ?')
            ->execute([$emptyProductId]);
    } catch (PDOException $exception) {
        $deleteEmptyProductError = $exception;
    }

    Assert::type(PDOException::class, $deleteEmptyProductError);
    Assert::same('45000', (string) $deleteEmptyProductError->getCode());

    // Produkt s variantou rovněž nelze fyzicky odstranit.
    $deleteProductError = null;

    try {
        $pdo->prepare('DELETE FROM product WHERE id = ?')
            ->execute([$productId]);
    } catch (PDOException $exception) {
        $deleteProductError = $exception;
    }

    Assert::type(PDOException::class, $deleteProductError);
    Assert::same('45000', (string) $deleteProductError->getCode());

    Assert::same(
        1,
        (int) $pdo->query('SELECT COUNT(*) FROM product WHERE id = ' . $productId)->fetchColumn(),
    );
    Assert::same(
        1,
        (int) $pdo->query('SELECT COUNT(*) FROM product_variant WHERE id = ' . $variantId)->fetchColumn(),
    );

    $foreignKey = $pdo->query(
        "SELECT DELETE_RULE
           FROM information_schema.REFERENTIAL_CONSTRAINTS
          WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'product_variant'
            AND CONSTRAINT_NAME = 'fk_product_variant_product_restrict'",
    )->fetchColumn();

    Assert::same('RESTRICT', $foreignKey);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
