--TEST--
Inventory product availability aggregates tracked variants and keeps untracked and missing records distinct
--FILE--
<?php

use App\Modules\Inventory\Application\ProductAvailabilityService;
use App\Modules\Inventory\Application\Dto\VariantAvailabilityStatus;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductAvailabilityService $service */
$service = $integration->container->getByType(ProductAvailabilityService::class);

$tshirt = $service->getByProductId(1);
Assert::same(1, $tshirt->productId);
Assert::count(6, $tshirt->variants);
Assert::same('130.000', $tshirt->trackedQuantityAvailable->toDecimalString());
Assert::same(0, $tshirt->untrackedVariantCount);
Assert::same(0, $tshirt->missingStockRecordCount);
Assert::false($tshirt->hasBackorderVariant);
Assert::same(VariantAvailabilityStatus::Tracked, $tshirt->variants[0]->status);
Assert::same('25.000', $tshirt->variants[0]->quantityAvailable?->toDecimalString());

$charger = $service->getByProductId(3);
Assert::same('97.000', $charger->trackedQuantityAvailable->toDecimalString());
Assert::true($charger->hasBackorderVariant);
Assert::same('0.000', $charger->variants[2]->quantityAvailable?->toDecimalString());
Assert::true($charger->variants[2]->allowBackorder);

Assert::same([], $service->getByProductId(PHP_INT_MAX)->variants);
Assert::exception(
    static fn () => $service->getByProductId(0),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
