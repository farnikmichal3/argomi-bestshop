--TEST--
Product variant editor updates SKU, EAN, weight and default atomically
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductVariantEditorReader;
use App\Modules\Catalog\Api\ProductVariantWriter;
use App\Modules\Catalog\Application\ProductVariantWriteRepository;
use App\Modules\Catalog\Application\ProductVariantWriteService;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
/** @var ProductVariantWriteRepository $repository */
$repository = $integration->container->getByType(ProductVariantWriteRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);
/** @var ProductVariantEditorReader $reader */
$reader = $integration->container->getByType(ProductVariantEditorReader::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);

$service = new ProductVariantWriteService(
    $repository,
    $transaction,
    new class implements CatalogPermission {
        public function canView(): bool
        {
            return true;
        }

        public function canManage(): bool
        {
            return true;
        }
    },
);

$token = bin2hex(random_bytes(5));
$rollbackMessage = 'rollback F5-18 test fixture';

try {
    $database->transaction(function () use ($database, $service, $reader, $localeProvider, $token, $rollbackMessage): void {
        $taxClass = $database->table('tax_class')->where('code', 'STANDARD')->fetch();
        Assert::notNull($taxClass);

        $product = $database->table('product')->insert([
            'code' => 'f518-' . $token,
            'status' => 'draft',
            'type' => 'physical',
            'tax_class_id' => (int) $taxClass->id,
        ]);
        $productId = (int) $product->id;

        $first = $database->table('product_variant')->insert([
            'product_id' => $productId,
            'sku' => 'F518A-' . $token,
            'combination_hash' => hash('sha256', 'f518-a-' . $token),
            'is_default' => 1,
            'is_active' => 1,
            'weight_g' => 100,
        ]);
        $second = $database->table('product_variant')->insert([
            'product_id' => $productId,
            'sku' => 'F518B-' . $token,
            'combination_hash' => hash('sha256', 'f518-b-' . $token),
            'is_default' => 0,
            'is_active' => 1,
        ]);

        $firstId = (int) $first->id;
        $secondId = (int) $second->id;
        $service->update($secondId, 'F518B-UPDATED-' . $token, '4006381333931', 250, true);

        $firstReloaded = $database->table('product_variant')->get($firstId);
        $secondReloaded = $database->table('product_variant')->get($secondId);
        Assert::same(0, (int) $firstReloaded->is_default);
        Assert::same(1, (int) $secondReloaded->is_default);
        Assert::same('4006381333931', $secondReloaded->ean);
        Assert::same(250, (int) $secondReloaded->weight_g);

        $data = $reader->findForEdit($secondId, $localeProvider->getDefault());
        Assert::notNull($data);
        Assert::same('F518B-UPDATED-' . $token, $data->sku);
        Assert::same('4006381333931', $data->ean);
        Assert::same(250, $data->weightG);
        Assert::true($data->isDefault);
        Assert::true($data->isActive);

        Assert::exception(
            fn () => $service->update($secondId, 'F518B-UPDATED-' . $token, '4006381333932', 250, true),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->update($secondId, 'F518B-UPDATED-' . $token, '4006381333931', -1, true),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->update($secondId, 'F518A-' . $token, null, 250, true),
            InvalidArgumentException::class,
        );

        $service->update($secondId, 'F518B-UPDATED-' . $token, null, null, false);
        Assert::same(0, (int) $database->table('product_variant')->get($secondId)->is_default);
        Assert::same(null, $database->table('product_variant')->get($secondId)->ean);
        Assert::same(null, $database->table('product_variant')->get($secondId)->weight_g);

        $database->table('product_variant')->where('id', $secondId)->update(['is_active' => 0]);
        Assert::exception(
            fn () => $service->update($secondId, 'F518B-UPDATED-' . $token, null, null, true),
            InvalidArgumentException::class,
        );

        throw new RuntimeException($rollbackMessage);
    });
} catch (RuntimeException $exception) {
    Assert::same($rollbackMessage, $exception->getMessage());
}

echo "OK\n";
?>
--EXPECT--
OK
