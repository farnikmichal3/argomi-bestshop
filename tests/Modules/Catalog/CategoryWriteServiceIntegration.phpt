<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\CategoryWriteService;
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
