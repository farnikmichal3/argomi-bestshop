<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Application\ProductWriteService;
use App\Modules\Tax\Api\TaxClassOptionReader;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\Products\ProductAdminDataGridFactory;
use App\UI\Admin\Products\ProductsPresenter;
use Nette\Application\ForbiddenRequestException;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductAdminDataGridFactory $gridFactory */
$gridFactory = $integration->container->getByType(ProductAdminDataGridFactory::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);

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

/** @var ProductEditorReader $productEditorReader */
$productEditorReader = $integration->container->getByType(ProductEditorReader::class);
/** @var TaxClassOptionReader $taxClassOptionReader */
$taxClassOptionReader = $integration->container->getByType(TaxClassOptionReader::class);
/** @var ProductWriteService $productWriter */
$productWriter = $integration->container->getByType(ProductWriteService::class);
/** @var CategoryTreeReader $categoryTreeReader */
$categoryTreeReader = $integration->container->getByType(CategoryTreeReader::class);
/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);

$presenter = new ProductsPresenter(
    $gridFactory,
    $deniedPermission,
    $localeProvider,
    $productEditorReader,
    $taxClassOptionReader,
    $productWriter,
    $categoryTreeReader,
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
