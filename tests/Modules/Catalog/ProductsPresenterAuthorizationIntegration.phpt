<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\Products\ProductsPresenter;
use Nette\Application\ForbiddenRequestException;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductAdminListReader $productReader */
$productReader = $integration->container->getByType(ProductAdminListReader::class);
/** @var CategoryTreeReader $categoryReader */
$categoryReader = $integration->container->getByType(CategoryTreeReader::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);

$deniedPermission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return false;
    }

    public function canManage(): bool
    {
        return false;
    }
};

$presenter = new ProductsPresenter(
    $productReader,
    $categoryReader,
    $deniedPermission,
    $localeProvider,
    $translatorFactory,
);

Assert::exception(
    static fn () => $presenter->renderDefault(),
    ForbiddenRequestException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
