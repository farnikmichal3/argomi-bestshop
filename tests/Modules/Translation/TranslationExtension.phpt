--TEST--
Translation module DI extension is discovered and compiled by the application Bootstrap
--FILE--
<?php

use App\Core\Module\ModuleRegistry;
use App\Modules\Translation\Api\LocaleNumberFormatter;
use App\Modules\Translation\Application\EntityTranslationResolver;
use App\Modules\Translation\Application\IcuLocaleNumberFormatter;
use App\Modules\Translation\Infrastructure\DI\TranslationExtension;
use Nette\DI\CompilerExtension;
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
Assert::type(
    IcuLocaleNumberFormatter::class,
    $context->container->getByType(LocaleNumberFormatter::class),
);

echo "OK\n";
?>
--EXPECT--
OK
