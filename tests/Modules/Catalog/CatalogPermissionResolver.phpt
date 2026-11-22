--TEST--
Catalog authorization requires authentication and distinguishes viewers from managers
--FILE--
<?php

use App\Modules\Catalog\Application\CatalogPermissionResolver;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$resolver = new CatalogPermissionResolver();

Assert::false($resolver->canView(false, ['catalog_manager']));
Assert::false($resolver->canManage(false, ['catalog_manager']));

Assert::true($resolver->canView(true, ['catalog_viewer']));
Assert::false($resolver->canManage(true, ['catalog_viewer']));

Assert::true($resolver->canView(true, ['catalog_manager']));
Assert::true($resolver->canManage(true, ['catalog_manager']));

Assert::true($resolver->canView(true, ['admin']));
Assert::true($resolver->canManage(true, ['admin']));

Assert::false($resolver->canView(true, ['customer']));
Assert::false($resolver->canManage(true, ['customer']));

echo "OK\n";
?>
--EXPECT--
OK
