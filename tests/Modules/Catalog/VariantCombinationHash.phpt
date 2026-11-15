--TEST--
Variant combination canonicalization is deterministic and rejects ambiguous values
--FILE--
<?php

use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
use InvalidArgumentException;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$attributes = [
    'size' => ['type' => 'enum', 'value' => 'm'],
    'color' => ['type' => 'enum', 'value' => 'black'],
];

$canonical = 'color=black;size=m';

Assert::same($canonical, VariantCombinationHash::canonicalize($attributes));
Assert::same($canonical, VariantCombinationHash::canonicalize(array_reverse($attributes)));
Assert::same(hash('sha256', $canonical), VariantCombinationHash::create($attributes));

Assert::same(
    'length_m=1;power_w=65',
    VariantCombinationHash::canonicalize([
        'power_w' => ['type' => 'number', 'value' => '65.000000'],
        'length_m' => ['type' => 'number', 'value' => '1.000'],
    ]),
);

Assert::same(
    'amount=0;negative=0.125',
    VariantCombinationHash::canonicalize([
        'negative' => ['type' => 'number', 'value' => '-0.125000'],
        'amount' => ['type' => 'number', 'value' => '-0.000'],
    ]),
);

Assert::same(
    'enabled=1;visible=0',
    VariantCombinationHash::canonicalize([
        'visible' => ['type' => 'bool', 'value' => false],
        'enabled' => ['type' => 'bool', 'value' => true],
    ]),
);

Assert::same(
    'label=Černá%3Bmodrá%3D100%25',
    VariantCombinationHash::canonicalize([
        'label' => ['type' => 'text', 'value' => 'Černá;modrá=100%'],
    ]),
);

$invalidInputs = [
    [],
    ['Bad-Code' => ['type' => 'enum', 'value' => 'black']],
    ['color' => ['type' => 'enum', 'value' => '']],
    ['length' => ['type' => 'number', 'value' => '1.0000001']],
    ['length' => ['type' => 'number', 'value' => '1,25']],
    ['length' => ['type' => 'number', 'value' => 1.25]],
    ['enabled' => ['type' => 'bool', 'value' => '1']],
    ['label' => ['type' => 'text', 'value' => "\xFF"]],
];

foreach ($invalidInputs as $invalidInput) {
    Assert::exception(
        static fn () => VariantCombinationHash::canonicalize($invalidInput),
        InvalidArgumentException::class,
    );
}

echo "OK\n";
?>
--EXPECT--
OK
