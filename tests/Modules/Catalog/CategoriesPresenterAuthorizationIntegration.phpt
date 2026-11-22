<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\CategoryWriter;
use App\Modules\Catalog\Api\CategoryEditorReader;
use App\Modules\Catalog\Api\CategoryOrderWriter;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Modules\Catalog\Application\CategoryManagementService;
use App\Modules\Catalog\Application\CategoryRepository;
use App\Modules\Catalog\Infrastructure\Api\CategoryTreeQueryService;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\CatalogModule\Categories\CategoriesPresenter;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\TemplateFactory;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var CatalogPermission $deniedPermission */
$deniedPermission = $integration->container->getByType(CatalogPermission::class);
/** @var CategoryTreeReader $deniedCategoryTree */
$deniedCategoryTree = $integration->container->getByType(CategoryTreeReader::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);

/** @var CategoryWriter $categoryWriter */
$categoryWriter = $integration->container->getByType(CategoryWriter::class);
/** @var CategoryEditorReader $categoryEditorReader */
$categoryEditorReader = $integration->container->getByType(CategoryEditorReader::class);
/** @var CategoryOrderWriter $categoryOrderWriter */
$categoryOrderWriter = $integration->container->getByType(CategoryOrderWriter::class);
/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);
$deniedPresenter = new CategoriesPresenter($deniedCategoryTree, $categoryWriter, $categoryEditorReader, $categoryOrderWriter, $deniedPermission, $localeProvider, $translatorFactory);
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

/** @var CategoryRepository $categoryRepository */
$categoryRepository = $integration->container->getByType(CategoryRepository::class);
$categoryManagement = new CategoryManagementService($categoryRepository, $viewerPermission);
$categoryTree = new CategoryTreeQueryService($categoryManagement);
$viewerPresenter = new CategoriesPresenter($categoryTree, $categoryWriter, $categoryEditorReader, $categoryOrderWriter, $viewerPermission, $localeProvider, $translatorFactory);

/** @var IRequest $httpRequest */
$httpRequest = $integration->container->getByType(IRequest::class);
/** @var IResponse $httpResponse */
$httpResponse = $integration->container->getByType(IResponse::class);
/** @var TemplateFactory $templateFactory */
$templateFactory = $integration->container->getByType(TemplateFactory::class);

$viewerPresenter->injectPrimary($httpRequest, $httpResponse, templateFactory: $templateFactory);
$viewerPresenter->renderDefault();

$groups = $viewerPresenter->template->categoryGroups;
$codes = [];
foreach ($groups as $group) {
    foreach ($group['rows'] as $row) {
        $codes[] = $row['category']->code;
    }
}

Assert::contains('clothing', $codes);
Assert::contains('electronics', $codes);
Assert::false($viewerPresenter->template->canManage);

echo "OK\n";
?>
--EXPECT--
OK
