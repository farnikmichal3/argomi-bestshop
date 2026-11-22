<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\CategoryOrderService;
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
/** @var CategoryWriteRepository $repository */
$repository = $integration->container->getByType(CategoryWriteRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var Explorer $database */
$database = $integration->database;

/** @var CategoryOrderService $deniedService */
$deniedService = $integration->container->getByType(CategoryOrderService::class);
Assert::exception(
    static fn () => $deniedService->reorder(null, []),
    CatalogAccessDeniedException::class,
);

$permission = new class implements CatalogPermission {
    public function canView(): bool { return true; }
    public function canManage(): bool { return true; }
};
$writer = new CategoryWriteService(
    $repository,
    $localeProvider,
    $integration->container->getByType(SlugService::class),
    $transaction,
    $permission,
);
$orderService = new CategoryOrderService($repository, $transaction, $permission);

$token = bin2hex(random_bytes(5));
$createdIds = [];
$translations = static function (string $prefix) use ($localeProvider, $token): array {
    $result = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $code = $locale->getCode();
        $result[$code] = [
            'name' => $prefix . '-' . $token . '-' . $code,
            'description' => null,
        ];
    }
    return $result;
};

try {
    $parentId = $writer->create(null, 'order-parent-' . $token, 0, true, $translations('order-parent'));
    $createdIds[] = $parentId;
    $firstId = $writer->create($parentId, 'order-first-' . $token, 20, true, $translations('order-first'));
    $createdIds[] = $firstId;
    $secondId = $writer->create($parentId, 'order-second-' . $token, 30, true, $translations('order-second'));
    $createdIds[] = $secondId;

    $orderService->reorder($parentId, [$secondId, $firstId]);
    Assert::same(0, (int) $database->table('category')->where('id', $secondId)->fetch()->sort_order);
    Assert::same(1, (int) $database->table('category')->where('id', $firstId)->fetch()->sort_order);

    Assert::exception(
        static fn () => $orderService->reorder($parentId, [$firstId]),
        InvalidArgumentException::class,
    );
    Assert::exception(
        static fn () => $orderService->reorder($parentId, [$firstId, $firstId]),
        InvalidArgumentException::class,
    );
    Assert::same(0, (int) $database->table('category')->where('id', $secondId)->fetch()->sort_order);
    Assert::same(1, (int) $database->table('category')->where('id', $firstId)->fetch()->sort_order);

    echo "OK\n";
} finally {
    foreach (array_reverse($createdIds) as $categoryId) {
        $database->table('category')->where('id', $categoryId)->delete();
    }
}
