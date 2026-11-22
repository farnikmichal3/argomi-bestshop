<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
$cookiePath = (new App\Core\Http\SessionCookieConfigurator())->configure(
    $_SERVER['REQUEST_URI'] ?? '/',
    $_SERVER['SCRIPT_NAME'] ?? '/index.php',
    $isHttps,
);

$bootstrap = new App\Bootstrap;
$container = $bootstrap->bootWebApplication();

$response = $container->getByType(Nette\Http\Response::class);
$response->cookiePath = $cookiePath;
$response->cookieSecure = $isHttps;

if (session_name() === 'ARGOMI_ADMIN_SESSID') {
    $container->getByType(Nette\Http\Session::class)->setOptions([
        'cookie_samesite' => 'Strict',
    ]);
}

$application = $container->getByType(Nette\Application\Application::class);
$application->run();
