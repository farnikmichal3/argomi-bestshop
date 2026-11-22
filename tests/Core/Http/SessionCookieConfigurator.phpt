--TEST--
Admin and public requests use separate cookies and server-side session storage
--FILE--
<?php

use App\Core\Http\SessionCookieConfigurator;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

$directory = sys_get_temp_dir() . '/argomi-session-' . bin2hex(random_bytes(6));
mkdir($directory, 0700);
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_strict_mode', '0');

$configurator = new SessionCookieConfigurator($directory);

$adminPolicy = $configurator->configure('/shop/admin/products?status=active', '/shop/index.php', true);
Assert::same('ARGOMI_ADMIN_SESSID', session_name());
Assert::same('/shop/admin', session_get_cookie_params()['path']);
Assert::true(session_get_cookie_params()['secure']);
Assert::true(session_get_cookie_params()['httponly']);
Assert::same('Strict', session_get_cookie_params()['samesite']);
Assert::same('/shop/admin', $adminPolicy['http']['cookiePath']);
Assert::same('Strict', $adminPolicy['session']['cookieSamesite']);
Assert::same($directory . '/admin', session_save_path());
Assert::same($directory . '/admin', $adminPolicy['session']['savePath']);

session_id(str_repeat('a', 32));
session_start();
$_SESSION['marker'] = 'admin';
session_write_close();

$publicPolicy = $configurator->configure('/shop/cs/products', '/shop/index.php', true);
Assert::same('ARGOMI_SESSID', session_name());
Assert::same('/shop/', session_get_cookie_params()['path']);
Assert::true(session_get_cookie_params()['secure']);
Assert::true(session_get_cookie_params()['httponly']);
Assert::same('Lax', session_get_cookie_params()['samesite']);
Assert::same('/shop/', $publicPolicy['http']['cookiePath']);
Assert::same('Lax', $publicPolicy['session']['cookieSamesite']);
Assert::same($directory . '/public', session_save_path());
Assert::same($directory . '/public', $publicPolicy['session']['savePath']);

session_id(str_repeat('b', 32));
session_start();
Assert::same([], $_SESSION);
$_SESSION['marker'] = 'public';
session_write_close();

$configurator->configure('/shop/admin', '/shop/index.php', true);
session_id(str_repeat('a', 32));
session_start();
Assert::same('admin', $_SESSION['marker']);
session_write_close();

$configurator->configure('/shop/cs/products', '/shop/index.php', false);
session_id(str_repeat('b', 32));
session_start();
Assert::same('public', $_SESSION['marker']);
session_write_close();

foreach (['admin', 'public'] as $scope) {
    foreach (glob($directory . '/' . $scope . '/sess_*') ?: [] as $sessionFile) {
        unlink($sessionFile);
    }
    rmdir($directory . '/' . $scope);
}
rmdir($directory);

echo "OK\n";
?>
--EXPECT--
OK
