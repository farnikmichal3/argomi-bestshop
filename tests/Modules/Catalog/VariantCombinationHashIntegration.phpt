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
    'TSHIRT-BK-M' => [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'enum', 'value' => 'm'],
    ],
    'CABLE-CC-1M-BK' => [
        'c_connectors' => ['type' => 'number', 'value' => '2'],
        'color' => ['type' => 'enum', 'value' => 'black'],
        'length_m' => ['type' => 'number', 'value' => '1.000000'],
    ],
    'GAN-1X65-WH' => [
        'color' => ['type' => 'enum', 'value' => 'white'],
        'power_w' => ['type' => 'number', 'value' => '65.000000'],
        'slots' => ['type' => 'number', 'value' => '1'],
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
