--TEST--
VariantSelector resolves complete active demo variant combinations and rejects invalid selections
--FILE--
<?php

use App\Modules\Catalog\Application\VariantSelector;
use App\Modules\Catalog\Infrastructure\Persistence\DatabaseVariantSelectionRepository;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var Explorer $database */
$database = $integration->database;
$selector = new VariantSelector(new DatabaseVariantSelectionRepository($database));

$tshirt = $selector->select(1, [
    'color' => ['type' => 'enum', 'value' => 'black'],
    'size' => ['type' => 'enum', 'value' => 'm'],
]);

Assert::same(
    (int) $database->table('product_variant')->where('sku', 'TSHIRT-BK-M')->fetch()->id,
    $tshirt,
    'The exact active T-shirt variant must be selected.',
);

$cable = $selector->select(2, [
    'c_connectors' => ['type' => 'number', 'value' => '2'],
    'color' => ['type' => 'enum', 'value' => 'black'],
    'length_m' => ['type' => 'number', 'value' => '1'],
]);

Assert::same(
    (int) $database->table('product_variant')->where('sku', 'CABLE-CC-1M-BK')->fetch()->id,
    $cable,
);

$charger = $selector->select(3, [
    'color' => ['type' => 'enum', 'value' => 'white'],
    'power_w' => ['type' => 'number', 'value' => '65'],
    'slots' => ['type' => 'number', 'value' => '1'],
]);

Assert::same(
    (int) $database->table('product_variant')->where('sku', 'GAN-1X65-WH')->fetch()->id,
    $charger,
);

Assert::same(null, $selector->select(999999, [
    'color' => ['type' => 'enum', 'value' => 'black'],
]));

Assert::exception(
    static fn () => $selector->select(1, [
        'color' => ['type' => 'enum', 'value' => 'black'],
    ]),
    InvalidArgumentException::class,
);

Assert::exception(
    static fn () => $selector->select(1, [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'text', 'value' => 'm'],
    ]),
    InvalidArgumentException::class,
);

Assert::same(null, $selector->select(1, [
    'color' => ['type' => 'enum', 'value' => 'black'],
    'size' => ['type' => 'enum', 'value' => 'xxl'],
]));

echo "OK\n";
?>
--EXPECT--
OK
