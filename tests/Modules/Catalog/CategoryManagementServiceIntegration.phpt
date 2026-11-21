--TEST--
Catalog category management queries require view permission
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\CategoryManagementService;
use App\Modules\Catalog\Application\CategoryRepository;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var CategoryManagementService $service */
$service = $integration->container->getByType(CategoryManagementService::class);

Assert::exception(
    static fn () => $service->findTree(Locale::fromString('cs')),
    CatalogAccessDeniedException::class,
);

$authorizedService = new CategoryManagementService(
    $integration->container->getByType(CategoryRepository::class),
    new class implements CatalogPermission {
        public function canView(): bool
        {
            return true;
        }

        public function canManage(): bool
        {
            return false;
        }
    },
);

$tree = $authorizedService->findTree(Locale::fromString('cs'));

Assert::count(2, $tree);
Assert::same(['clothing', 'electronics'], array_map(
    static fn ($node): string => $node->category->code,
    $tree,
));

echo "OK\n";
?>
--EXPECT--
OK
