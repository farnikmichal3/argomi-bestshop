<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryDeletionBlockedException;
use App\Modules\Catalog\Application\CategoryWriteRepository;
use App\Modules\Catalog\Application\CategoryWriteService;
use App\Modules\Catalog\Application\SlugService;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var CategoryWriteService $deniedService */
$deniedService = $integration->container->getByType(CategoryWriteService::class);
Assert::exception(
    static fn () => $deniedService->delete(999999999),
    CatalogAccessDeniedException::class,
);

/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var Explorer $database */
$database = $integration->database;
$service = new CategoryWriteService(
    $integration->container->getByType(CategoryWriteRepository::class),
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

$token = bin2hex(random_bytes(4));
$parentId = null;
$childId = null;
$productCategoryId = null;
$productId = null;

$translations = static function (string $name) use ($localeProvider, $token): array {
    $result = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $code = $locale->getCode();
        $result[$code] = [
            'name' => $name . '-' . $token . '-' . $code,
            'description' => 'Category deletion integration test',
        ];
    }
    return $result;
};

try {
    $parentId = $service->create(null, 'delete-parent-' . $token, 0, true, $translations('delete-parent'));
    $childId = $service->create($parentId, 'delete-child-' . $token, 0, true, $translations('delete-child'));

    $childBlock = Assert::exception(
        static fn () => $service->delete($parentId),
        CategoryDeletionBlockedException::class,
    );
    Assert::same(CategoryDeletionBlockedException::HAS_CHILDREN, $childBlock->reason);

    $service->delete($childId);
    Assert::same(null, $database->table('category')->where('id', $childId)->fetch());
    Assert::same(0, $database->table('category_translation')->where('category_id', $childId)->count('*'));

    $service->delete($parentId);
    Assert::same(null, $database->table('category')->where('id', $parentId)->fetch());
    Assert::same(0, $database->table('category_translation')->where('category_id', $parentId)->count('*'));

    $product = $database->table('product')->fetch();
    Assert::notNull($product);
    $productId = (int) $product->id;
    $parentId = $service->create(null, 'delete-assigned-' . $token, 0, true, $translations('delete-assigned'));
    $assignment = $database->table('product_category')->insert([
        'product_id' => $productId,
        'category_id' => $parentId,
        'sort_order' => 0,
    ]);
    $productCategoryId = (int) $assignment->id;

    $productBlock = Assert::exception(
        static fn () => $service->delete($parentId),
        CategoryDeletionBlockedException::class,
    );
    Assert::same(CategoryDeletionBlockedException::HAS_PRODUCTS, $productBlock->reason);
    Assert::same(1, $database->table('product_category')->where('category_id', $parentId)->count('*'));
    Assert::same(1, $database->table('category')->where('id', $parentId)->count('*'));

    echo "OK\n";
} finally {
    if ($productCategoryId !== null) {
        $database->table('product_category')->where('id', $productCategoryId)->delete();
    }
    if ($childId !== null) {
        $database->table('category')->where('id', $childId)->delete();
    }
    if ($parentId !== null) {
        $database->table('category')->where('id', $parentId)->delete();
    }
}
?>
--EXPECT--
OK
