--TEST--
Product variant generation is transactional, idempotent and validates complete typed combinations
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\ProductVariantGenerationRepository;
use App\Modules\Catalog\Application\ProductVariantGenerationService;
use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
use App\Shared\Application\Transaction\Transaction;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
/** @var ProductVariantGenerationRepository $repository */
$repository = $integration->container->getByType(ProductVariantGenerationRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);

$service = new ProductVariantGenerationService(
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
$rollbackMessage = 'rollback F5-17 test fixture';

try {
    $database->transaction(function () use ($database, $service, $token, $rollbackMessage): void {
        $taxClass = $database->table('tax_class')->where('code', 'STANDARD')->fetch();
        Assert::notNull($taxClass);

        $product = $database->table('product')->insert([
            'code' => 'f517-' . $token,
            'status' => 'draft',
            'type' => 'physical',
            'tax_class_id' => (int) $taxClass->id,
        ]);
        $productId = (int) $product->id;

        // Reuse the seeded enum axes color and size without mutating shared demo data.
        $database->table('product_variant_axis')->insert([
            'product_id' => $productId,
            'attribute_id' => 1,
            'sort_order' => 1,
        ]);
        $database->table('product_variant_axis')->insert([
            'product_id' => $productId,
            'attribute_id' => 2,
            'sort_order' => 2,
        ]);

        $combination = [
            'color' => ['type' => 'enum', 'value' => 'white'],
            'size' => ['type' => 'enum', 'value' => 'l'],
        ];
        $hash = VariantCombinationHash::create($combination);

        Assert::same(1, $service->generate($productId, [$combination]));
        Assert::same(0, $service->generate($productId, [$combination]));

        $variant = $database->table('product_variant')
            ->where('product_id', $productId)
            ->where('combination_hash', $hash)
            ->fetch();
        Assert::notNull($variant);
        Assert::same('V' . $productId . '-' . substr($hash, 0, 40), $variant->sku);
        Assert::same(1, (int) $variant->is_active);
        Assert::same(0, (int) $variant->is_default);
        Assert::same(2, $database->table('product_variant_attribute_value')
            ->where('product_variant_id', (int) $variant->id)
            ->count('*'));

        Assert::exception(
            fn () => $service->generate($productId, [[
                'color' => ['type' => 'enum', 'value' => 'white'],
            ]]),
            InvalidArgumentException::class,
        );

        Assert::exception(
            static fn () => $service->generate($productId, [[
                'color' => ['type' => 'enum', 'value' => 'unknown'],
                'size' => ['type' => 'enum', 'value' => 'l'],
            ]]),
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
