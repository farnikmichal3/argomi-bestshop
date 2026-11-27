<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Modules\Catalog\Application\ProductWriteRepository;
use App\Modules\Catalog\Application\ProductWriteService;
use App\Modules\Catalog\Application\SlugService;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Tax\Api\TaxClassOptionReader;
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
            'description' => '<p>Description ' . $token . '</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(2)">safe link</a>',
            'metaTitle' => 'Meta ' . $token,
            'metaDescription' => 'Meta description ' . $token,
        ];
    }
    return $entries;
};

$productId = null;
$variantId = null;
try {
    Assert::exception(
        fn () => $service->create($code, ProductAdminStatus::Draft, ProductAdminType::Physical, $taxClassId, $translations('denied'), [$categoryId]),
        CatalogAccessDeniedException::class,
    );
    Assert::same(null, $database->table('product')->where('code', $code)->fetch());

    $service = new ProductWriteService(
        $integration->container->getByType(ProductWriteRepository::class),
        $localeProvider,
        $integration->container->getByType(SlugService::class),
        $integration->container->getByType(Transaction::class),
        $integration->container->getByType(\App\Modules\Catalog\Application\ProductDescriptionSanitizer::class),
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

    Assert::exception(
        fn () => $service->create($code . '-active', ProductAdminStatus::Active, ProductAdminType::Physical, $taxClassId, $translations('active-create'), []),
        InvalidArgumentException::class,
    );
    Assert::same(null, $database->table('product')->where('code', $code . '-active')->fetch());

    $productId = $service->create(
        $code,
        ProductAdminStatus::Draft,
        ProductAdminType::Physical,
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

    $updatedTranslations = $translations('updated-product');
    Assert::exception(
        fn () => $service->update($productId, $code, ProductAdminStatus::Active, ProductAdminType::Digital, $taxClassId, $updatedTranslations, []),
        InvalidArgumentException::class,
    );
    Assert::same('draft', $database->table('product')->where('id', $productId)->fetch()->status);

    // Aktivace bez varianty ani s aktivní, ale nevýchozí variantou nesmí projít.
    $variant = $database->table('product_variant')->insert([
        'product_id' => $productId,
        'sku' => 'TEST-' . strtoupper($token),
        'combination_hash' => hash('sha256', 'test-activation-' . $token),
        'is_default' => 0,
        'is_active' => 1,
    ]);
    $variantId = (int) $variant->id;
    Assert::exception(
        fn () => $service->update($productId, $code, ProductAdminStatus::Active, ProductAdminType::Digital, $taxClassId, $updatedTranslations, []),
        InvalidArgumentException::class,
    );
    $database->table('product_variant')->where('id', $variantId)->update(['is_active' => 0, 'is_default' => 1]);
    Assert::exception(
        fn () => $service->update($productId, $code, ProductAdminStatus::Active, ProductAdminType::Digital, $taxClassId, $updatedTranslations, []),
        InvalidArgumentException::class,
    );
    $database->table('product_variant')->where('id', $variantId)->update(['is_active' => 1]);

    $service->update(
        $productId,
        $code,
        ProductAdminStatus::Active,
        ProductAdminType::Digital,
        $taxClassId,
        $updatedTranslations,
        [],
    );
    Assert::same('active', $database->table('product')->where('id', $productId)->fetch()->status);
    Assert::same('digital', $database->table('product')->where('id', $productId)->fetch()->type);
    Assert::same(0, $database->table('product_category')->where('product_id', $productId)->count('*'));
    foreach ($updatedTranslations as $localeCode => $expectedTranslation) {
        $storedTranslation = $database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $localeCode)
            ->fetch();
        Assert::notNull($storedTranslation);
        Assert::same($expectedTranslation['name'], $storedTranslation->name);
        Assert::contains('<p>Description ' . $token . '</p>', (string) $storedTranslation->description);
        Assert::notContains('<script', (string) $storedTranslation->description);
        Assert::notContains('javascript:', (string) $storedTranslation->description);
        Assert::notContains('onclick=', (string) $storedTranslation->description);
        Assert::same($expectedTranslation['metaTitle'], $storedTranslation->meta_title);
    }
    $updatedEditorData = $editorReader->findForEdit($productId);
    Assert::notNull($updatedEditorData);
    foreach ($updatedEditorData->translations as $storedTranslation) {
        Assert::notContains('<script', (string) $storedTranslation['description']);
        Assert::notContains('javascript:', (string) $storedTranslation['description']);
    }

    Assert::exception(
        fn () => $service->update($productId, 'invalid code', ProductAdminStatus::Draft, ProductAdminType::Physical, $taxClassId, $translations('invalid'), []),
        InvalidArgumentException::class,
    );

    echo "OK\n";
} finally {
    if ($productId !== null) {
        // Produkty zůstávají kvůli auditní stopě; testovací variantu odstraníme a produkt archivujeme.
        $database->table('product_category')->where('product_id', $productId)->delete();
        if ($variantId !== null) {
            // Projekt chrání varianty před fyzickým smazáním; testovací záznam deaktivujeme.
            $database->table('product_variant')->where('id', $variantId)->update([
                'is_active' => 0,
                'is_default' => 0,
            ]);
        }
        $database->table('product')->where('id', $productId)->update(['status' => ProductStatus::Archived->value]);
    }
    $database->table('category')->where('id', $categoryId)->delete();
}
?>
--EXPECT--
OK
