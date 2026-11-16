--TEST--
Translation module provides a concrete DI extension registered in the module registry
--FILE--
<?php

use App\Core\Module\ModuleRegistry;
use App\Modules\Translation\Infrastructure\DI\TranslationExtension;
use Nette\DI\CompilerExtension;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

Assert::contains('Translation', ModuleRegistry::MODULES);
Assert::true(class_exists(TranslationExtension::class));
Assert::true(is_subclass_of(TranslationExtension::class, CompilerExtension::class));
Assert::false((new ReflectionClass(TranslationExtension::class))->isAbstract());

echo "OK\n";
?>
--EXPECT--
OK
