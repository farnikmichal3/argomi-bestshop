<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\Dto\ProductVariantMatrix;
use App\Modules\Catalog\Api\Dto\VariantMatrixAxis;
use App\Modules\Catalog\Api\Dto\VariantMatrixCombination;
use App\Modules\Catalog\Api\Dto\VariantMatrixValue;
use App\Modules\Catalog\Api\ProductVariantGenerator;
use App\Modules\Catalog\Api\ProductVariantMatrixReader;
use App\Modules\Catalog\Api\ProductVariantEditorReader;
use App\Modules\Catalog\Api\ProductVariantWriter;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\Products\Variants\VariantsPresenter;
use App\UI\Admin\Products\Variant\VariantPresenter;
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

$managerPermission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return true;
    }
};

$matrixReader = new class implements ProductVariantMatrixReader {
    public function findForProduct(int $productId, \App\Shared\Domain\Locale\Locale $locale): ?ProductVariantMatrix
    {
        return new ProductVariantMatrix(
            $productId,
            'test-product',
            'Test product',
            [
                new VariantMatrixAxis(
                    1,
                    'color',
                    'Color',
                    'enum',
                    [new VariantMatrixValue('red', 'Red')],
                ),
            ],
            [
                new VariantMatrixCombination(
                    str_repeat('a', 64),
                    ['color' => ['type' => 'enum', 'value' => 'red']],
                    'Color: Red',
                    null,
                    null,
                    null,
                    null,
                ),
            ],
        );
    }
};

$generator = new class implements ProductVariantGenerator {
    public function generate(int $productId, array $combinations): int
    {
        return 0;
    }
};

/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);
/** @var IRequest $httpRequest */
$httpRequest = $integration->container->getByType(IRequest::class);
/** @var IResponse $httpResponse */
$httpResponse = $integration->container->getByType(IResponse::class);
/** @var TemplateFactory $templateFactory */
$templateFactory = $integration->container->getByType(TemplateFactory::class);

$viewerPresenter = new VariantsPresenter(
    $matrixReader,
    $generator,
    $viewerPermission,
    $localeProvider,
    $translatorFactory,
);
$viewerPresenter->injectPrimary($httpRequest, $httpResponse, templateFactory: $templateFactory);
$viewerPresenter->actionDefault(1);

Assert::false($viewerPresenter->template->canManage);
Assert::false($viewerPresenter->template->canGenerate);
Assert::exception(
    static fn () => $viewerPresenter->getComponent('generateForm'),
    ForbiddenRequestException::class,
);

$managerPresenter = new VariantsPresenter(
    $matrixReader,
    $generator,
    $managerPermission,
    $localeProvider,
    $translatorFactory,
);
$managerPresenter->injectPrimary($httpRequest, $httpResponse, templateFactory: $templateFactory);
$managerPresenter->actionDefault(1);

Assert::true($managerPresenter->template->canManage);
Assert::true($managerPresenter->template->canGenerate);

$variantPresenter = new VariantPresenter(
    $integration->container->getByType(ProductVariantEditorReader::class),
    $integration->container->getByType(ProductVariantWriter::class),
    $viewerPermission,
    $localeProvider,
    $translatorFactory,
);
Assert::exception(
    static fn () => $variantPresenter->actionEdit(1),
    ForbiddenRequestException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
