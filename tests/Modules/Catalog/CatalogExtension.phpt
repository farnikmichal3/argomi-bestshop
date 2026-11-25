--TEST--
Catalog and Media module DI services are discovered and compiled by the application Bootstrap
--FILE--
<?php

use App\Core\Module\ModuleRegistry;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Infrastructure\DI\CatalogExtension;
use App\Modules\Catalog\Infrastructure\Security\NetteCatalogPermissionService;
use App\Modules\Media\Api\MediaAssetContentReader;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Modules\Media\Api\MediaPermission;
use App\Modules\Media\Application\MediaFileStorage;
use App\Modules\Media\Application\MediaImageProcessor;
use App\Modules\Media\Infrastructure\DI\MediaExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Tests\Support\IntegrationTestBootstrap;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

Assert::contains('Catalog', ModuleRegistry::MODULES);
Assert::contains('Media', ModuleRegistry::MODULES);
Assert::true(class_exists(CatalogExtension::class));
Assert::true(class_exists(MediaExtension::class));
Assert::true(is_subclass_of(CatalogExtension::class, CompilerExtension::class));
Assert::true(is_subclass_of(MediaExtension::class, CompilerExtension::class));
Assert::false((new ReflectionClass(CatalogExtension::class))->isAbstract());
Assert::false((new ReflectionClass(MediaExtension::class))->isAbstract());

// Skutečná kompilace ověří konvenci názvů extension a Bootstrap registraci.
$context = IntegrationTestBootstrap::boot($rootDir);
Assert::true($context->container instanceof Container);

// Katalogový read kontrakt zůstává dostupný přes automatické vyhledávání služeb.
Assert::type(ProductEditorReader::class, $context->container->getByType(ProductEditorReader::class));

// Media zveřejňuje pouze API; storage a procesor zůstávají interními kontrakty modulu.
Assert::type(MediaAssetReader::class, $context->container->getByType(MediaAssetReader::class));
Assert::type(MediaAssetWriter::class, $context->container->getByType(MediaAssetWriter::class));
Assert::type(MediaAssetContentReader::class, $context->container->getByType(MediaAssetContentReader::class));
Assert::type(MediaFileStorage::class, $context->container->getByType(MediaFileStorage::class));
Assert::type(MediaImageProcessor::class, $context->container->getByType(MediaImageProcessor::class));

// Oprávnění jsou skutečně navázána na Nette User.
$catalogPermission = $context->container->getByType(CatalogPermission::class);
Assert::type(NetteCatalogPermissionService::class, $catalogPermission);
Assert::false($catalogPermission->canView());
Assert::false($catalogPermission->canManage());

$mediaPermission = $context->container->getByType(MediaPermission::class);
Assert::false($mediaPermission->canView());
Assert::false($mediaPermission->canManage());

echo "OK\n";
?>
--EXPECT--
OK
