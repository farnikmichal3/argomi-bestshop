--TEST--
Catalog product type migration adds the constrained type column and index
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

$column = $pdo->query(
    "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'product'
       AND COLUMN_NAME = 'type'",
)->fetch(PDO::FETCH_ASSOC);

Assert::same([
    'COLUMN_TYPE' => "enum('physical','digital','service')",
    'IS_NULLABLE' => 'NO',
    'COLUMN_DEFAULT' => 'physical',
], $column);

$index = $pdo->query(
    "SELECT COUNT(*)
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'product'
       AND INDEX_NAME = 'idx_product_type'
       AND COLUMN_NAME = 'type'",
)->fetchColumn();

Assert::same(1, (int) $index);

// Stávající demo produkt musí po dopředné migraci zůstat fyzickým zbožím.
$existingProductType = $database->table('product')
    ->where('id', 1)
    ->fetchField('type');

Assert::same('physical', $existingProductType);

echo "OK\n";
?>
--EXPECT--
OK
