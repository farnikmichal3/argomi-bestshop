--TEST--
Translation module DI extension is discovered and compiled by the application Bootstrap
--FILE--
<?php

use App\Core\Module\ModuleRegistry;
use App\Modules\Translation\Api\LocaleNumberFormatter;
use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\TranslationDiagnostics;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationPermission;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Modules\Translation\Application\EntityTranslationResolver;
use App\Modules\Translation\Application\IcuLocaleNumberFormatter;
use App\Modules\Translation\Application\MissingTranslationReporter;
use App\Modules\Translation\Infrastructure\Console\TranslationsCheckCommand;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationDiagnostics;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationPanel;
use App\Modules\Translation\Infrastructure\Diagnostics\NetteTranslationDiagnosticsService;
use App\Modules\Translation\Infrastructure\Diagnostics\TranslationKeyUsageScanner;
use App\Modules\Translation\Infrastructure\DI\TranslationExtension;
use App\Modules\Translation\Infrastructure\Security\NetteTranslationPermissionService;
use Nette\DI\CompilerExtension;
use App\Shared\Domain\Locale\Locale;
use Nette\DI\Container;
use Tests\Support\IntegrationTestBootstrap;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

Assert::contains('Translation', ModuleRegistry::MODULES);
Assert::true(class_exists(TranslationExtension::class));
Assert::true(is_subclass_of(TranslationExtension::class, CompilerExtension::class));
Assert::false((new ReflectionClass(TranslationExtension::class))->isAbstract());

// Kompilace skutečného kontejneru ověří, že Bootstrap extension objeví podle konvence.
$context = IntegrationTestBootstrap::boot($rootDir);
Assert::true($context->container instanceof Container);
Assert::type(EntityTranslationResolver::class, $context->container->getByType(EntityTranslationResolver::class));
Assert::type(TranslationKeyUsageScanner::class, $context->container->getByType(TranslationKeyUsageScanner::class));
Assert::type(TranslationsCheckCommand::class, $context->container->getByType(TranslationsCheckCommand::class));
Assert::type(
    IcuLocaleNumberFormatter::class,
    $context->container->getByType(LocaleNumberFormatter::class),
);

// Veřejné oprávnění musí být skutečně navázáno na Nette User přes automatické DI.
$permission = $context->container->getByType(TranslationPermission::class);
Assert::type(NetteTranslationPermissionService::class, $permission);
Assert::false($permission->canView());
Assert::false($permission->canManage());

// F4-07: ověření celé runtime diagnostické cesty přes skutečný DI kontejner.
$reporter = $context->container->getByType(MissingTranslationReporter::class);
Assert::type(MissingTranslationDiagnostics::class, $reporter);
$diagnosticsService = $context->container->getByType(TranslationDiagnostics::class);
Assert::type(NetteTranslationDiagnosticsService::class, $diagnosticsService);
$panel = $context->container->getByType(MissingTranslationPanel::class);
Assert::type(MissingTranslationPanel::class, $panel);

$translatorFactory = $context->container->getByType(TranslatorFactory::class);
$translator = $translatorFactory->createTranslator(
    TranslationDomain::Catalog,
    Locale::fromString('cs'),
);
$missingKey = 'f4_07.integration.missing';
Assert::same($missingKey, $translator->translate($missingKey));
Assert::same(1, count($reporter->getMissing()));
Assert::contains($missingKey, $panel->getPanel());

echo "OK\n";
?>
--EXPECT--
OK
