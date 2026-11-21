--TEST--
Catalog module DI extension is discovered and compiled by the application Bootstrap
--FILE--
<?php

use App\\Core\\Module\\ModuleRegistry;
use App\\Modules\\Catalog\\Api\\CatalogPermission;
use App\\Modules\\Catalog\\Application\\ProductImageStorage;
use App\\Modules\\Catalog\\Application\\ProductImageProcessor;
use App\\Modules\\Catalog\\Application\\ProductRepository;
use App\\Modules\\Catalog\\Infrastructure\\DI\\CatalogExtension;
use App\\Modules\\Catalog\\Infrastructure\\Security\\NetteCatalogPermissionService;
use Nette\\DI\\CompilerExtension;
use Nette\\DI\\Container;
use Tests\\Support\\IntegrationTestBootstrap;
use Tester\\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

Assert::contains('Catalog', ModuleRegistry::MODULES);
Assert::true(class_exists(CatalogExtension::class));
Assert::true(is_subclass_of(CatalogExtension::class, CompilerExtension::class));
Assert::false((new ReflectionClass(CatalogExtension::class))->isAbstract());

// Skutečná kompilace ověří konvenci názvu CatalogExtension a Bootstrap registraci.
$context = IntegrationTestBootstrap::boot($rootDir);
Assert::true($context->container instanceof Container);

// Existující čtecí kontrakt musí zůstat dostupný přes automatické vyhledávání služeb.
Assert::type(ProductRepository::class, $context->container->getByType(ProductRepository::class));
Assert::type(ProductImageStorage::class, $context->container->getByType(ProductImageStorage::class));
Assert::type(ProductImageProcessor::class, $context->container->getByType(ProductImageProcessor::class));

// Veřejné oprávnění musí být skutečně navázáno na Nette User přes automatické DI.
$permission = $context->container->getByType(CatalogPermission::class);
Assert::type(NetteCatalogPermissionService::class, $permission);
Assert::false($permission->canView());
Assert::false($permission->canManage());

echo "OK\\n";
?>
--EXPECT--
OK
