--TEST--
Catalog admin product listing includes all lifecycle states and batches localized product and category names
--FILE--
<?php

use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductAdminListReader $reader */
$reader = $integration->container->getByType(ProductAdminListReader::class);

$locale = Locale::fromString('cs');
$all = $reader->findProducts(new ProductAdminListFilter(), $locale);

Assert::true($all->totalItems >= 3);
Assert::same(1, $all->page);
Assert::same(12, $all->perPage);
Assert::same(1, $all->items[0]->id);
Assert::same('tshirt-printed', $all->items[0]->code);
Assert::same('Tričko s potiskem', $all->items[0]->name);
Assert::same(ProductAdminType::Physical, $all->items[0]->type);
Assert::same(ProductAdminStatus::Active, $all->items[0]->status);
Assert::same(['Oblečení'], $all->items[0]->categoryNames);

// Kategorie omezuje výsledky na produkty přímo přiřazené do dané kategorie.
$clothing = $reader->findProducts(
    new ProductAdminListFilter(categoryId: 1),
    $locale,
);
Assert::same(1, $clothing->totalItems);
Assert::same([1], array_map(static fn ($item): int => $item->id, $clothing->items));

// Typ i stav se kombinují s kategorií a jsou skutečnými enum hodnotami.
$physicalActiveClothing = $reader->findProducts(
    new ProductAdminListFilter(
        categoryId: 1,
        status: ProductStatus::Active,
        type: ProductType::Physical,
    ),
    $locale,
);
Assert::same(1, $physicalActiveClothing->totalItems);
Assert::same(1, $physicalActiveClothing->items[0]->id);

// Nové typy zatím nemají demo záznamy; filtr nesmí zaměňovat typ za stav.
$digital = $reader->findProducts(
    new ProductAdminListFilter(type: ProductType::Digital),
    $locale,
);
Assert::same(0, $digital->totalItems);
Assert::same([], $digital->items);

// Stránkování je deterministické podle ID a probíhá před načtením překladů.
$firstPage = $reader->findProducts(
    new ProductAdminListFilter(page: 1, perPage: 1),
    $locale,
);
$secondPage = $reader->findProducts(
    new ProductAdminListFilter(page: 2, perPage: 1),
    $locale,
);
Assert::same(1, $firstPage->items[0]->id);
Assert::same(2, $secondPage->items[0]->id);
Assert::same($all->totalItems, $secondPage->totalItems);
Assert::same($all->totalItems, $secondPage->totalPages);

echo "OK\n";
?>
--EXPECT--
OK
