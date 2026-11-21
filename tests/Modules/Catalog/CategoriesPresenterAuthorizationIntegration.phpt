<?php

declare(strict_types=1);

use App\\Modules\\Catalog\\Api\\CatalogPermission;
use App\\Modules\\Catalog\\Application\\CategoryManagementService;
use App\\Modules\\Catalog\\Application\\CategoryRepository;
use App\\Shared\\Domain\\Locale\\LocaleProvider;
use App\\UI\\Admin\\Catalog\\CategoriesPresenter;
use App\\Modules\\Catalog\\Api\\CatalogAccessDeniedException;
use Nette\\Application\\ForbiddenRequestException;
use Tester\\Assert;
use Tests\\Support\\IntegrationTestBootstrap;

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

$viewerPermission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return false;
    }
};

$viewerManagement = new CategoryManagementService(
    $integration->container->getByType(CategoryRepository::class),
    $viewerPermission,
);
$viewerPresenter = new CategoriesPresenter($viewerManagement, $viewerPermission, $localeProvider);
$viewerPresenter->renderDefault();

Assert::count(2, $viewerPresenter->template->categories);
Assert::false($viewerPresenter->template->canManage);

echo "OK\\n";
?>
--EXPECT--
OK
