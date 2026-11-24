<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Tax\Api\TaxClassOptionReader;
use App\Modules\Catalog\Application\ProductWriteRepository;
use App\Modules\Catalog\Application\ProductWriteService;
use App\Modules\Catalog\Application\SlugService;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var ProductWriteService $service */
$service = $integration->container->getByType(ProductWriteService::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var Explorer $database */
$database = $integration->database;

$token = bin2hex(random_bytes(4));
$code = 'test-product-' . $token;
$categoryCode = 'test-product-category-' . $token;
$categoryRow = $database->table('category')->insert(['code' => $categoryCode]);
$categoryId = (int) $categoryRow->id;
$taxClass = $database->table('tax_class')->order('id ASC')->fetch();
Assert::notNull($taxClass);
$taxClassId = (int) $taxClass->id;

$translations = static function (string $prefix) use ($localeProvider, $token): array {
    $entries = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $entries[$locale->getCode()] = [
            'name' => $prefix . '-' . $token . '-' . $locale->getCode(),
            'shortDescription' => 'Short ' . $token,
            'description' => 'Description ' . $token,
            'metaTitle' => 'Meta ' . $token,
            'metaDescription' => 'Meta description ' . $token,
        ];
    }
    return $entries;
};

$productId = null;
try {
    Assert::exception(
        fn () => $service->create($code, ProductStatus::Draft, ProductType::Physical, $taxClassId, $translations('denied'), [$categoryId]),
        CatalogAccessDeniedException::class,
    );
    Assert::same(null, $database->table('product')->where('code', $code)->fetch());

    $service = new ProductWriteService(
        $integration->container->getByType(ProductWriteRepository::class),
        $localeProvider,
        $integration->container->getByType(SlugService::class),
        $integration->container->getByType(Transaction::class),
        new class implements CatalogPermission {
            public function canView(): bool
            {
                return true;
            }

            public function canManage(): bool
            {
                return true;
            }
        },
    );

    $productId = $service->create(
        $code,
        ProductStatus::Draft,
        ProductType::Physical,
        $taxClassId,
        $translations('created-product'),
        [$categoryId],
    );
    Assert::true($productId > 0);
    Assert::same('draft', $database->table('product')->where('id', $productId)->fetch()->status);
    Assert::count(count($localeProvider->getActiveLocales()), $database->table('product_translation')->where('product_id', $productId)->fetchAll());
    Assert::same(1, $database->table('product_category')->where('product_id', $productId)->count('*'));

    /** @var ProductEditorReader $editorReader */
    $editorReader = $integration->container->getByType(ProductEditorReader::class);
    $editorData = $editorReader->findForEdit($productId);
    Assert::notNull($editorData);
    Assert::same($productId, $editorData->id);
    Assert::same([$categoryId], $editorData->categoryIds);
    Assert::count(count($localeProvider->getActiveLocales()), $editorData->translations);
    /** @var TaxClassOptionReader $taxClassOptionReader */
    $taxClassOptionReader = $integration->container->getByType(TaxClassOptionReader::class);
    Assert::true(count($taxClassOptionReader->findOptions()) > 0);

    $service->update(
        $productId,
        $code,
        ProductStatus::Active,
        ProductType::Digital,
        $taxClassId,
        $translations('updated-product'),
        [],
    );
    Assert::same('active', $database->table('product')->where('id', $productId)->fetch()->status);
    Assert::same('digital', $database->table('product')->where('id', $productId)->fetch()->type);
    Assert::same(0, $database->table('product_category')->where('product_id', $productId)->count('*'));

    Assert::exception(
        fn () => $service->update($productId, 'invalid code', ProductStatus::Draft, ProductType::Physical, $taxClassId, $translations('invalid'), []),
        InvalidArgumentException::class,
    );

    echo "OK\n";
} finally {
    if ($productId !== null) {
        $database->table('product')->where('id', $productId)->delete();
    }
    $database->table('category')->where('id', $categoryId)->delete();
}
?>
--EXPECT--
OK
