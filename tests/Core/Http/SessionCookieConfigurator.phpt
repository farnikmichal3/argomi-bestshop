--TEST--
Admin and public session policies use distinct cookies and storage directories
--FILE--
<?php

use App\Core\Http\SessionCookieConfigurator;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

$directory = sys_get_temp_dir() . '/argomi-session-' . bin2hex(random_bytes(6));
$normalizePath = static fn (string $path): string => str_replace('\\\\', '/', $path);
$configurator = new SessionCookieConfigurator($directory);

$adminPolicy = $configurator->resolvePolicy('/shop/admin/products?status=active', '/shop/index.php', true);
Assert::same('ARGOMI_ADMIN_SESSID', $adminPolicy['sessionName']);
Assert::same('/shop/admin', $adminPolicy['http']['cookiePath']);
Assert::true($adminPolicy['http']['cookieSecure']);
Assert::same('Strict', $adminPolicy['session']['cookieSamesite']);
Assert::same($normalizePath($directory . '/admin'), $normalizePath($adminPolicy['session']['savePath']));
Assert::true(is_dir($directory . '/admin'));

$publicPolicy = $configurator->resolvePolicy('/shop/cs/products', '/shop/index.php', true);
Assert::same('ARGOMI_SESSID', $publicPolicy['sessionName']);
Assert::same('/shop/', $publicPolicy['http']['cookiePath']);
Assert::true($publicPolicy['http']['cookieSecure']);
Assert::same('Lax', $publicPolicy['session']['cookieSamesite']);
Assert::same($normalizePath($directory . '/public'), $normalizePath($publicPolicy['session']['savePath']));
Assert::true(is_dir($directory . '/public'));
Assert::notSame($adminPolicy['session']['savePath'], $publicPolicy['session']['savePath']);

$publicHttpPolicy = $configurator->resolvePolicy('/shop/cs/products', '/shop/index.php', false);
Assert::false($publicHttpPolicy['http']['cookieSecure']);

rmdir($directory . '/admin');
rmdir($directory . '/public');
rmdir($directory);

echo "OK\n";
?>
--EXPECT--
OK
