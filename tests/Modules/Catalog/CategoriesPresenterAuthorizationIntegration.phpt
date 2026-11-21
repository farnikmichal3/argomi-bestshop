<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\CategoryManagementService;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\Catalog\CategoriesPresenter;
use Nette\Application\ForbiddenRequestException;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var CatalogPermission $deniedPermission */
$deniedPermission = $integration->container->getByType(CatalogPermission::class);
/** @var CategoryManagementService $management */
$management = $integration->container->getByType(CategoryManagementService::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);

$deniedPresenter = new CategoriesPresenter($management, $deniedPermission, $localeProvider);
Assert::exception(
    static fn () => $deniedPresenter->renderDefault(),
    ForbiddenRequestException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
