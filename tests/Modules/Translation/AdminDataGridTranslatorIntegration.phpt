<?php

declare(strict_types=1);

use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\Locale;
use App\UI\Admin\DataGrid\AdminDataGridTranslator;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);

$cs = new AdminDataGridTranslator(
    $translatorFactory->createTranslator(TranslationDomain::Catalog, Locale::fromString('cs')),
    $translatorFactory->createTranslator(TranslationDomain::Admin, Locale::fromString('cs')),
);

Assert::same('Položky', $cs->translate('contributte_datagrid.items'));
Assert::same('Předchozí', $cs->translate('contributte_datagrid.previous'));
Assert::same('Produkty', $cs->translate('products.admin.title'));

$en = new AdminDataGridTranslator(
    $translatorFactory->createTranslator(TranslationDomain::Catalog, Locale::fromString('en')),
    $translatorFactory->createTranslator(TranslationDomain::Admin, Locale::fromString('en')),
);

Assert::same('Items', $en->translate('contributte_datagrid.items'));
Assert::same('Next', $en->translate('contributte_datagrid.next'));
Assert::same('Products', $en->translate('products.admin.title'));

echo "OK\n";
?>
--EXPECT--
OK
