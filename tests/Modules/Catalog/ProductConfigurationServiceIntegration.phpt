<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\ProductConfigurationRepository;
use App\Modules\Catalog\Application\ProductConfigurationService;
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
$token = bin2hex(random_bytes(4));
$productId = 0;
try {
    $database->transaction(function () use ($database, $integration, $token, &$productId): void {
        $product = $database->table('product')->insert([
    'code' => 'test-config-' . $token,
    'status' => 'draft',
    'type' => 'physical',
    'tax_class_id' => $database->table('tax_class')->order('id ASC')->fetch()->id,
        ]);
        $productId = (int) $product->id;
        $attributeIds = [];
        $enumValueId = null;
        foreach ([['text', 'material'], ['number', 'length'], ['enum', 'finish'], ['bool', 'waterproof']] as [$type, $code]) {
        $row = $database->table('attribute')->insert(['code' => 'test-' . $code . '-' . $token, 'type' => $type]);
        $attributeIds[$code] = (int) $row->id;
    }
    $enumValue = $database->table('attribute_value')->insert([
        'attribute_id' => $attributeIds['finish'],
        'code' => 'matte-' . $token,
    ]);
    $enumValueId = (int) $enumValue->id;

    $service = new ProductConfigurationService(
        $integration->container->getByType(ProductConfigurationRepository::class),
        $integration->container->getByType(Transaction::class),
        new class implements CatalogPermission {
            public function canView(): bool { return true; }
            public function canManage(): bool { return true; }
        },
    );

    $service->save($productId, [
        ['attributeId' => $attributeIds['material'], 'value' => 'Cotton'],
        ['attributeId' => $attributeIds['length'], 'value' => '12.500'],
        ['attributeId' => $attributeIds['finish'], 'value' => $enumValueId],
        ['attributeId' => $attributeIds['waterproof'], 'value' => true],
    ], [$attributeIds['finish'], $attributeIds['waterproof']]);

    Assert::same(4, $database->table('product_attribute_value')->where('product_id', $productId)->count('*'));
    Assert::same(1, $database->table('product_variant_axis')->where('product_id', $productId)->count('*') > 0 ? 1 : 0);
    Assert::same(1, (int) $database->table('product_attribute_value')->where('product_id', $productId)->where('attribute_id', $attributeIds['waterproof'])->fetch()->value_number);

    $variant = $database->table('product_variant')->insert([
        'product_id' => $productId,
        'sku' => 'TEST-' . strtoupper($token),
        'combination_hash' => hash('sha256', $token),
    ]);
    $service->save($productId, [
        ['attributeId' => $attributeIds['material'], 'value' => 'Linen'],
    ], [$attributeIds['waterproof'], $attributeIds['finish']]);
    Assert::same([$attributeIds['waterproof'], $attributeIds['finish']], array_map(
        static fn($row): int => (int) $row->attribute_id,
        $database->table('product_variant_axis')->where('product_id', $productId)->order('sort_order ASC')->fetchAll(),
    ));

    Assert::exception(
        fn () => $service->save($productId, [], [$attributeIds['waterproof']]),
        InvalidArgumentException::class,
    );
    Assert::same(4, $database->table('product_attribute_value')->where('product_id', $productId)->count('*'));

    Assert::exception(
        fn () => $service->save($productId, [
            ['attributeId' => $attributeIds['finish'], 'value' => $attributeIds['material']],
        ], [$attributeIds['finish']]),
        InvalidArgumentException::class,
    );

        throw new RuntimeException('rollback product configuration fixture');
    });
} catch (RuntimeException $exception) {
    Assert::same('rollback product configuration fixture', $exception->getMessage());
}

echo "OK\n";
?>
--EXPECT--
OK
