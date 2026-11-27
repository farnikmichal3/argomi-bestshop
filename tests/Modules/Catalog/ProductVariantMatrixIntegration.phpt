--TEST--
Product variant matrix reads axes, value options, existing variants and missing combinations
--FILE--
<?php

use App\Modules\Catalog\Api\ProductVariantMatrixReader;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var ProductVariantMatrixReader $reader */
$reader = $integration->container->getByType(ProductVariantMatrixReader::class);

$tshirt = $reader->findForProduct(1, Locale::fromString('cs'));
Assert::notNull($tshirt);
Assert::same('tshirt-printed', $tshirt->productCode);
Assert::count(2, $tshirt->axes);
Assert::count(6, $tshirt->combinations);
Assert::same(0, count(array_filter($tshirt->combinations, static fn ($combination): bool => !$combination->exists())));

$cable = $reader->findForProduct(2, Locale::fromString('cs'));
Assert::notNull($cable);
Assert::count(3, $cable->axes);
Assert::count(8, $cable->combinations);
Assert::same(5, count(array_filter($cable->combinations, static fn ($combination): bool => $combination->exists())));
Assert::same(3, count(array_filter($cable->combinations, static fn ($combination): bool => !$combination->exists())));

$lengthAxis = null;
foreach ($cable->axes as $axis) {
    if ($axis->code === 'length_m') {
        $lengthAxis = $axis;
    }
}
Assert::notNull($lengthAxis);
Assert::same(['1', '2'], array_map(static fn ($value): string => (string) $value->value, $lengthAxis->values));

$charger = $reader->findForProduct(3, Locale::fromString('cs'));
Assert::notNull($charger);
Assert::count(3, $charger->axes);
Assert::count(12, $charger->combinations);
Assert::same(4, count(array_filter($charger->combinations, static fn ($combination): bool => $combination->exists())));
Assert::same(8, count(array_filter($charger->combinations, static fn ($combination): bool => !$combination->exists())));

$chargerAxisValues = [];
foreach ($charger->axes as $axis) {
    $chargerAxisValues[$axis->code] = array_map(
        static fn ($value): string => (string) $value->value,
        $axis->values,
    );
}
Assert::same(['black', 'white'], $chargerAxisValues['color']);
Assert::same(['1', '2', '3'], $chargerAxisValues['slots']);
Assert::same(['65', '100'], $chargerAxisValues['power_w']);

Assert::same(null, $reader->findForProduct(999999, Locale::fromString('cs')));

echo "OK\n";
?>
--EXPECT--
OK
