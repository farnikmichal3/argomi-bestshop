<?php

declare(strict_types=1);

use App\\Modules\\Catalog\\Application\\CategoryWriteService;
use InvalidArgumentException;
use Tester\\Assert;
use Tests\\Support\\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$service = $integration->container->getByType(CategoryWriteService::class);

Assert::exception(
    static fn () => $service->create(null, 'INVALID CODE', 0, true, []),
    InvalidArgumentException::class,
);

echo "OK\\n";
?>
--EXPECT--
OK
