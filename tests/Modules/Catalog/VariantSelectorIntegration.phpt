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

// Demo tričko má úplnou matici 2 barev × 3 velikostí. Ověříme každou kombinaci.
$tshirtCombinations = [
    'TSHIRT-BK-S' => ['color' => 'black', 'size' => 's'],
    'TSHIRT-BK-M' => ['color' => 'black', 'size' => 'm'],
    'TSHIRT-BK-L' => ['color' => 'black', 'size' => 'l'],
    'TSHIRT-WH-S' => ['color' => 'white', 'size' => 's'],
    'TSHIRT-WH-M' => ['color' => 'white', 'size' => 'm'],
    'TSHIRT-WH-L' => ['color' => 'white', 'size' => 'l'],
];

foreach ($tshirtCombinations as $sku => $values) {
    $selectedVariantId = $selector->select(1, [
        'color' => ['type' => 'enum', 'value' => $values['color']],
        'size' => ['type' => 'enum', 'value' => $values['size']],
    ]);

    $expectedVariant = $database->table('product_variant')
        ->where('sku', $sku)
        ->fetch();

    Assert::notNull($expectedVariant, sprintf('Demo T-shirt variant %s must exist.', $sku));
    Assert::same(
        (int) $expectedVariant->id,
        $selectedVariantId,
        sprintf('The exact active T-shirt variant %s must be selected.', $sku),
    );
}

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
