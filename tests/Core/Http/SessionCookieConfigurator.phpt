--TEST--
Admin and public requests select isolated PHP session cookies
--FILE--
<?php

declare(strict_types=1);

use App\Core\Http\SessionCookieConfigurator;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

$configurator = new SessionCookieConfigurator();

$configurator->configure('/shop/admin/products?status=active', '/shop/index.php', true);
Assert::same('ARGOMI_ADMIN_SESSID', session_name());
Assert::same('/shop/admin', session_get_cookie_params()['path']);
Assert::true(session_get_cookie_params()['secure']);
Assert::true(session_get_cookie_params()['httponly']);
Assert::same('Strict', session_get_cookie_params()['samesite']);

$configurator->configure('/shop/cs/products', '/shop/index.php', true);
Assert::same('ARGOMI_SESSID', session_name());
Assert::same('/shop/', session_get_cookie_params()['path']);
Assert::true(session_get_cookie_params()['secure']);
Assert::true(session_get_cookie_params()['httponly']);
Assert::same('Lax', session_get_cookie_params()['samesite']);

$configurator->configure('/shop/administrator', '/shop/index.php', false);
Assert::same('ARGOMI_SESSID', session_name());
Assert::same('/shop/', session_get_cookie_params()['path']);
Assert::false(session_get_cookie_params()['secure']);

echo "OK\n";
?>
--EXPECT--
OK
