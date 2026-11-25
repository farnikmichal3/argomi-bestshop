<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\ProductImageRegistrationService;
use App\Modules\Catalog\Application\ProductImageRepository;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Shared\Application\Transaction\Transaction;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

$deniedPermission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return false;
    }

    public function canManage(): bool
    {
        return false;
    }
};

$service = new ProductImageRegistrationService(
    $integration->container->getByType(MediaAssetWriter::class),
    $integration->container->getByType(MediaAssetReader::class),
    $integration->container->getByType(ProductImageRepository::class),
    $integration->container->getByType(Transaction::class),
    $deniedPermission,
);

Assert::exception(
    static fn () => $service->register(1, null, 'not-an-image'),
    CatalogAccessDeniedException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
