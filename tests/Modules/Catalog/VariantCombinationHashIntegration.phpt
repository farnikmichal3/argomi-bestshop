--TEST--
Variant combination SHA-256 matches MySQL SHA2 and existing demo variant hashes
--FILE--
<?php

use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
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
$statement = $pdo->prepare('SELECT SHA2(?, 256)');

$fixtures = [
    // Demo produkt 1 – bavlněné tričko (6 kombinací).
    'TSHIRT-BK-S' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'enum', 'value' => 's'],
    ],
    'TSHIRT-BK-M' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'enum', 'value' => 'm'],
    ],
    'TSHIRT-BK-L' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'enum', 'value' => 'l'],
    ],
    'TSHIRT-WH-S' => [
        'color' => ['type' => 'enum', 'value' => 'white'],
        'size' => ['type' => 'enum', 'value' => 's'],
    ],
    'TSHIRT-WH-M' => [
        'color' => ['type' => 'enum', 'value' => 'white'],
        'size' => ['type' => 'enum', 'value' => 'm'],
    ],
    'TSHIRT-WH-L' => [
        'color' => ['type' => 'enum', 'value' => 'white'],
        'size' => ['type' => 'enum', 'value' => 'l'],
    ],

    // Demo produkt 2 – GaN nabíječka (4 existující kombinace).
    'GAN-1X65-WH' => [
        'color' => ['type' => 'enum', 'value' => 'white'],
        'power_w' => ['type' => 'number', 'value' => '65.000000'],
        'slots' => ['type' => 'number', 'value' => '1'],
    ],
    'GAN-2X65-BK' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'power_w' => ['type' => 'number', 'value' => '65.000000'],
        'slots' => ['type' => 'number', 'value' => '2'],
    ],
    'GAN-2X100-BK' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'power_w' => ['type' => 'number', 'value' => '100.000000'],
        'slots' => ['type' => 'number', 'value' => '2'],
    ],
    'GAN-3X100-BK' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'power_w' => ['type' => 'number', 'value' => '100.000000'],
        'slots' => ['type' => 'number', 'value' => '3'],
    ],

    // Demo produkt 3 – USB-C kabel (5 existujících kombinací).
    'CABLE-CC-1M-BK' => [
        'c_connectors' => ['type' => 'number', 'value' => '2'],
        'color' => ['type' => 'enum', 'value' => 'black'],
        'length_m' => ['type' => 'number', 'value' => '1.000000'],
    ],
    'CABLE-CC-2M-BK' => [
        'c_connectors' => ['type' => 'number', 'value' => '2'],
        'color' => ['type' => 'enum', 'value' => 'black'],
        'length_m' => ['type' => 'number', 'value' => '2.000000'],
    ],
    'CABLE-CC-2M-WH' => [
        'c_connectors' => ['type' => 'number', 'value' => '2'],
        'color' => ['type' => 'enum', 'value' => 'white'],
        'length_m' => ['type' => 'number', 'value' => '2.000000'],
    ],
    'CABLE-CA-1M-BK' => [
        'c_connectors' => ['type' => 'number', 'value' => '1'],
        'color' => ['type' => 'enum', 'value' => 'black'],
        'length_m' => ['type' => 'number', 'value' => '1.000000'],
    ],
    'CABLE-CA-2M-BK' => [
        'c_connectors' => ['type' => 'number', 'value' => '1'],
        'color' => ['type' => 'enum', 'value' => 'black'],
        'length_m' => ['type' => 'number', 'value' => '2.000000'],
    ],
];

foreach ($fixtures as $sku => $attributes) {
    $canonical = VariantCombinationHash::canonicalize($attributes);
    $phpHash = VariantCombinationHash::create($attributes);

    $statement->execute([$canonical]);
    $sqlHash = $statement->fetchColumn();

    Assert::same($phpHash, $sqlHash, sprintf('PHP and MySQL SHA-256 differ for %s.', $sku));

    $variant = $database->table('product_variant')
        ->where('sku', $sku)
        ->fetch();

    Assert::notNull($variant, sprintf('Demo variant %s is missing.', $sku));
    Assert::same($phpHash, $variant->combination_hash, sprintf('Demo seed hash differs for %s.', $sku));
}

echo "OK\n";
?>
--EXPECT--
OK
