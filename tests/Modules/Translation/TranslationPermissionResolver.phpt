--TEST--
Translation permissions require authentication and distinguish read-only viewers from managers
--FILE--
<?php

use App\Modules\Translation\Application\TranslationPermissionResolver;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$resolver = new TranslationPermissionResolver();

Assert::false($resolver->canView(false, ['translation_manager']));
Assert::false($resolver->canManage(false, ['translation_manager']));

Assert::true($resolver->canView(true, ['translation_viewer']));
Assert::false($resolver->canManage(true, ['translation_viewer']));

Assert::true($resolver->canView(true, ['translation_manager']));
Assert::true($resolver->canManage(true, ['translation_manager']));

Assert::false($resolver->canView(true, ['customer']));
Assert::false($resolver->canManage(true, ['customer']));

echo "OK\n";
?>
--EXPECT--
OK
