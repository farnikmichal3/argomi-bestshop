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

// USB-C kabel (produkt ID 2): délka × počet konektorů × barva.
$cableVariants = [
    'CABLE-CC-1M-BK' => ['c_connectors' => '2', 'color' => 'black', 'length_m' => '1'],
    'CABLE-CC-2M-BK' => ['c_connectors' => '2', 'color' => 'black', 'length_m' => '2'],
    'CABLE-CC-2M-WH' => ['c_connectors' => '2', 'color' => 'white', 'length_m' => '2'],
    'CABLE-CA-1M-BK' => ['c_connectors' => '1', 'color' => 'black', 'length_m' => '1'],
    'CABLE-CA-2M-BK' => ['c_connectors' => '1', 'color' => 'black', 'length_m' => '2'],
];

foreach ($cableVariants as $sku => $values) {
    $selectedVariantId = $selector->select(2, [
        'c_connectors' => ['type' => 'number', 'value' => $values['c_connectors']],
        'color' => ['type' => 'enum', 'value' => $values['color']],
        'length_m' => ['type' => 'number', 'value' => $values['length_m']],
    ]);

    $expectedVariant = $database->table('product_variant')
        ->where('sku', $sku)
        ->fetch();

    Assert::notNull($expectedVariant, sprintf('Demo USB-C cable variant %s must exist.', $sku));
    Assert::same(
        (int) $expectedVariant->id,
        $selectedVariantId,
        sprintf('The exact active USB-C cable variant %s must be selected.', $sku),
    );
}

// Z os 1/2 konektory × 1/2 m × černá/bílá chybí tři záměrné kombinace.
$missingCableCombinations = [
    ['c_connectors' => '1', 'color' => 'white', 'length_m' => '1'],
    ['c_connectors' => '1', 'color' => 'white', 'length_m' => '2'],
    ['c_connectors' => '2', 'color' => 'white', 'length_m' => '1'],
];

foreach ($missingCableCombinations as $values) {
    Assert::same(null, $selector->select(2, [
        'c_connectors' => ['type' => 'number', 'value' => $values['c_connectors']],
        'color' => ['type' => 'enum', 'value' => $values['color']],
        'length_m' => ['type' => 'number', 'value' => $values['length_m']],
    ]));
}

// GaN nabíječka (produkt ID 3) má čtyři skutečné kombinace ze tří os.
$chargerVariants = [
    'GAN-1X65-WH' => ['color' => 'white', 'power_w' => '65', 'slots' => '1'],
    'GAN-2X65-BK' => ['color' => 'black', 'power_w' => '65', 'slots' => '2'],
    'GAN-2X100-BK' => ['color' => 'black', 'power_w' => '100', 'slots' => '2'],
    'GAN-3X100-BK' => ['color' => 'black', 'power_w' => '100', 'slots' => '3'],
];

foreach ($chargerVariants as $sku => $values) {
    $selectedVariantId = $selector->select(3, [
        'color' => ['type' => 'enum', 'value' => $values['color']],
        'power_w' => ['type' => 'number', 'value' => $values['power_w']],
        'slots' => ['type' => 'number', 'value' => $values['slots']],
    ]);

    $expectedVariant = $database->table('product_variant')
        ->where('sku', $sku)
        ->fetch();

    Assert::notNull($expectedVariant, sprintf('Demo GaN charger variant %s must exist.', $sku));
    Assert::same(
        (int) $expectedVariant->id,
        $selectedVariantId,
        sprintf('The exact active GaN charger variant %s must be selected.', $sku),
    );
}

// Ověříme i osm kombinací, které v demo nabídce záměrně neexistují.
$missingChargerCombinations = [
    ['color' => 'black', 'power_w' => '65', 'slots' => '1'],
    ['color' => 'white', 'power_w' => '65', 'slots' => '2'],
    ['color' => 'black', 'power_w' => '65', 'slots' => '3'],
    ['color' => 'white', 'power_w' => '65', 'slots' => '3'],
    ['color' => 'black', 'power_w' => '100', 'slots' => '1'],
    ['color' => 'white', 'power_w' => '100', 'slots' => '1'],
    ['color' => 'white', 'power_w' => '100', 'slots' => '2'],
    ['color' => 'white', 'power_w' => '100', 'slots' => '3'],
];

foreach ($missingChargerCombinations as $values) {
    Assert::same(null, $selector->select(3, [
        'color' => ['type' => 'enum', 'value' => $values['color']],
        'power_w' => ['type' => 'number', 'value' => $values['power_w']],
        'slots' => ['type' => 'number', 'value' => $values['slots']],
    ]));
}

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
