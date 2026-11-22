<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$httpsValue = $_SERVER['HTTPS'] ?? null;
$requestUri = $_SERVER['REQUEST_URI'] ?? null;
$scriptName = $_SERVER['SCRIPT_NAME'] ?? null;

$isHttps = is_string($httpsValue) && $httpsValue !== '' && $httpsValue !== 'off';
$sessionConfig = (new App\Core\Http\SessionCookieConfigurator())->configure(
    is_string($requestUri) ? $requestUri : '/',
    is_string($scriptName) ? $scriptName : '/index.php',
    $isHttps,
);

$bootstrap = new App\Bootstrap;
$container = $bootstrap->bootWebApplication($sessionConfig);
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
