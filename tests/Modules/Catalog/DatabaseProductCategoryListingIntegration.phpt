--TEST--
Catalog product repository lists active category products with stable pagination and sorting
--FILE--
<?php

use App\\Modules\\Catalog\\Application\\ProductRepository;
use App\\Modules\\Catalog\\Application\\ProductSortOrder;
use App\\Shared\\Domain\\Locale\\Locale;
use Tester\\Assert;
use Tests\\Support\\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductRepository $repository */
$repository = $integration->container->getByType(ProductRepository::class);

$czech = Locale::fromString('cs');
$english = Locale::fromString('en');

$tshirts = $repository->findByCategory(1, $czech);

Assert::count(1, $tshirts->items);
Assert::same(1, $tshirts->totalItems);
Assert::same(1, $tshirts->page);
Assert::same(12, $tshirts->perPage);
Assert::same(1, $tshirts->totalPages);
Assert::same(1, $tshirts->items[0]->product->id);
Assert::same('Tričko s potiskem', $tshirts->items[0]->translation?->name);

$englishTshirts = $repository->findByCategory(1, $english);
Assert::same('Printed T-shirt', $englishTshirts->items[0]->translation?->name);

// Kategorie vrací pouze produkty přiřazené přímo do ní, nikoli z potomků.
$electronics = $repository->findByCategory(2, $czech);
Assert::same([], $electronics->items);
Assert::same(0, $electronics->totalItems);
Assert::same(0, $electronics->totalPages);

$cables = $repository->findByCategory(
    3,
    $czech,
    1,
    1,
    ProductSortOrder::ProductCodeAscending,
);
Assert::count(1, $cables->items);
Assert::same('usb-c-cable', $cables->items[0]->product->code);
Assert::same('USB-C kabel', $cables->items[0]->translation?->name);

$chargers = $repository->findByCategory(
    4,
    $czech,
    1,
    1,
    ProductSortOrder::Newest,
);
Assert::same('gan-charger', $chargers->items[0]->product->code);

// Stránka za posledním výsledkem je prázdná, metadata celkového počtu zůstávají.
$emptyPage = $repository->findByCategory(1, $czech, 2, 1);
Assert::same([], $emptyPage->items);
Assert::same(1, $emptyPage->totalItems);
Assert::same(1, $emptyPage->totalPages);

// Neexistující ani neaktivní kategorie neposkytne katalogové produkty.
$missingCategory = $repository->findByCategory(PHP_INT_MAX, $czech);
Assert::same([], $missingCategory->items);
Assert::same(0, $missingCategory->totalItems);

Assert::exception(
    static fn () => $repository->findByCategory(0, $czech),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $repository->findByCategory(1, $czech, 0),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $repository->findByCategory(1, $czech, 1, 101),
    InvalidArgumentException::class,
);

echo "OK\\n";
?>
--EXPECT--
OK
