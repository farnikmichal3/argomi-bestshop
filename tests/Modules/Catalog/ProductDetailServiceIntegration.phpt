--TEST--
Catalog product detail service loads localized product data in bounded batch queries
--FILE--
<?php

use App\Modules\Catalog\Application\ProductDetailService;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductDetailService $service */
$service = $integration->container->getByType(ProductDetailService::class);

$czech = $service->getById(1, Locale::fromString('cs'));

Assert::notNull($czech);
Assert::same(1, $czech->product->id);
Assert::same('Tričko s potiskem', $czech->translation?->name);
Assert::count(6, $czech->variants);
Assert::same('TSHIRT-BK-S', $czech->variants[0]->sku);
Assert::true($czech->variants[0]->isDefault);
Assert::same(180, $czech->variants[0]->weightG);
Assert::count(2, $czech->variants[0]->attributeValues);

$variantValues = [];
foreach ($czech->variants[0]->attributeValues as $value) {
    $variantValues[$value->code] = $value;
}

Assert::same('Barva', $variantValues['color']->name);
Assert::same('black', $variantValues['color']->value);
Assert::same('Černá', $variantValues['color']->label);
Assert::same('Velikost', $variantValues['size']->name);
Assert::same('S', $variantValues['size']->label);

// Demo produkt zatím nemá přiřazené obrázky ani produktové atributy.
Assert::same([], $czech->images);
Assert::same([], $czech->attributes);
Assert::same([], $czech->relations);

Assert::count(2, $czech->options);
Assert::same('print', $czech->options[0]->code);
Assert::same('Potisk', $czech->options[0]->name);
Assert::true($czech->options[0]->isRequired);
Assert::count(2, $czech->options[0]->values);
Assert::same('Vlastní potisk', $czech->options[0]->values[1]->label);
Assert::same('print_text', $czech->options[1]->code);
Assert::same('Text potisku', $czech->options[1]->name);
Assert::same([], $czech->options[1]->values);

$english = $service->getById(1, Locale::fromString('en'));
Assert::notNull($english);
Assert::same('Printed T-shirt', $english->translation?->name);
Assert::same('Color', $english->variants[0]->attributeValues[0]->name);

$charger = $service->getById(3, Locale::fromString('cs'));
Assert::notNull($charger);
Assert::count(1, $charger->attributes);
Assert::same('gan', $charger->attributes[0]->code);
Assert::same('Technologie GaN', $charger->attributes[0]->name);
Assert::same('1.000000', $charger->attributes[0]->value);
Assert::count(1, $charger->relations);
Assert::same(2, $charger->relations[0]->productId);
Assert::same('usb-c-cable', $charger->relations[0]->productCode);
Assert::same('accessory', $charger->relations[0]->type);
Assert::same('USB-C kabel', $charger->relations[0]->translation?->name);

Assert::same(null, $service->getById(PHP_INT_MAX, Locale::fromString('cs')));
Assert::exception(
    static fn () => $service->getById(0, Locale::fromString('cs')),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
