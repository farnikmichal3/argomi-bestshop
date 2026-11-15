<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Dočasný vstupní bod pro ověření, že prostředí testing vypíná Tracy debug režim.
// Po dokončení ověření tento soubor odstraň.
$bootstrap = new App\Bootstrap('local.testing.neon');
$container = $bootstrap->bootWebApplication();
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
