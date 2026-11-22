<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
$sessionConfig = (new App\Core\Http\SessionCookieConfigurator())->configure(
    $_SERVER['REQUEST_URI'] ?? '/',
    $_SERVER['SCRIPT_NAME'] ?? '/index.php',
    $isHttps,
);

$bootstrap = new App\Bootstrap;
$container = $bootstrap->bootWebApplication($sessionConfig);
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
