<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
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
/** @var CategoryWriteService $service */
$service = $integration->container->getByType(CategoryWriteService::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var Explorer $database */
$database = $integration->database;

$token = bin2hex(random_bytes(4));
$rootCode = 'test-category-' . $token;
$childCode = 'test-child-' . $token;
$rootId = null;
$childId = null;

$translations = static function (string $namePrefix) use ($localeProvider, $token): array {
    $entries = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $code = $locale->getCode();
        $entries[$code] = [
            'name' => $namePrefix . '-' . $token . '-' . $code,
            'description' => 'Integration test ' . $token,
        ];
    }
    return $entries;
};

// Skutečná DI služba musí před jakýmkoli čtením či zápisem odmítnout anonymního uživatele.
$deniedCode = 'denied-category-' . $token;
Assert::exception(
    fn () => $service->create(null, $deniedCode, 0, true, $translations('denied')),
    CatalogAccessDeniedException::class,
);
Assert::same(null, $database->table('category')->where('code', $deniedCode)->fetch());
Assert::exception(
    fn () => $service->update(999999999, null, 'denied-update-' . $token, 0, true, $translations('denied')),
    CatalogAccessDeniedException::class,
);

// Ostatní scénáře používají explicitní testovací oprávnění správce.
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

try {
    $rootId = $service->create(null, $rootCode, 15, true, $translations('root-category'));
    Assert::true($rootId > 0);

    $rootRows = $database->table('category_translation')->where('category_id', $rootId)->fetchAll();
    Assert::count(count($localeProvider->getActiveLocales()), $rootRows);
    foreach ($rootRows as $row) {
        Assert::same('root-category-' . $token . '-' . $row->locale, $row->slug);
    }

    $childId = $service->create($rootId, $childCode, 2, true, $translations('child-category'));
    Assert::true($childId > 0);
    Assert::same($rootId, (int) $database->table('category')->where('id', $childId)->fetch()->parent_id);

    $service->update($rootId, null, $rootCode, 20, false, $translations('updated-root'));
    $updatedRoot = $database->table('category')->where('id', $rootId)->fetch();
    Assert::same(20, (int) $updatedRoot->sort_order);
    Assert::same(0, (int) $updatedRoot->is_active);

    Assert::exception(
        fn () => $service->create(null, $rootCode, 0, true, $translations('duplicate')),
        InvalidArgumentException::class,
    );

    Assert::exception(
        fn () => $service->update($rootId, $childId, $rootCode, 0, true, $translations('cycle')),
        InvalidArgumentException::class,
    );

    echo "OK\n";
} finally {
    if ($childId !== null) {
        $database->table('category')->where('id', $childId)->delete();
    }
    if ($rootId !== null) {
        $database->table('category')->where('id', $rootId)->delete();
    }
}
?>
--EXPECT--
OK
