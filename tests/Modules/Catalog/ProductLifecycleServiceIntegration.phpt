--TEST--
Product lifecycle archives permanently and creates an independent safe draft copy
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\ProductLifecycleRepository;
use App\Modules\Catalog\Application\ProductLifecycleService;
use App\Modules\Catalog\Domain\Product\ProductStatus;
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
/** @var ProductLifecycleRepository $repository */
$repository = $integration->container->getByType(ProductLifecycleRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);

$permission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return true;
    }
};
$service = new ProductLifecycleService($repository, $transaction, $permission);

$token = bin2hex(random_bytes(6));
$taxClass = $database->table('tax_class')->order('id ASC')->fetch();
Assert::notNull($taxClass);
$source = $database->table('product')->insert([
    'code' => 'f5-25-source-' . $token,
    'status' => 'active',
    'type' => 'physical',
    'tax_class_id' => $taxClass->id,
]);
$sourceId = (int) $source->id;
$database->table('product_translation')->insert([
    'product_id' => $sourceId,
    'locale' => 'cs',
    'name' => 'Testovací produkt ' . $token,
    'slug' => 'f5-25-source-' . $token,
    'short_description' => 'Krátký popis',
    'description' => '<p>Popis</p>',
    'meta_title' => 'SEO title',
    'meta_description' => 'SEO description',
]);
$sourceVariant = $database->table('product_variant')->insert([
    'product_id' => $sourceId,
    'sku' => 'F525-' . strtoupper($token),
    'ean' => '859' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
    'combination_hash' => hash('sha256', 'f5-25-' . $token),
    'is_default' => 1,
    'is_active' => 1,
    'weight_g' => 250,
    'length_mm' => 300,
    'width_mm' => 200,
    'height_mm' => 20,
    'sort_order' => 3,
]);
$sourceVariantId = (int) $sourceVariant->id;
$currency = $database->table('currency')->order('code ASC')->fetch();
Assert::notNull($currency);
$database->table('variant_price')->insert([
    'product_variant_id' => $sourceVariantId,
    'currency' => $currency->code,
    'amount_minor' => 1299,
    'tax_included' => 1,
]);
$database->table('stock_item')->insert([
    'product_variant_id' => $sourceVariantId,
    'track_stock' => 0,
    'allow_backorder' => 1,
    'quantity_on_hand' => '15.000',
    'quantity_reserved' => '3.000',
]);
$mediaAsset = $database->table('media_asset')->insert([
    'original_filename' => 'shirt-' . $token . '.jpg',
    'storage_key' => 'catalog/test/' . $token . '.jpg',
]);
$sourceImage = $database->table('product_image')->insert([
    'product_id' => $sourceId,
    'media_asset_id' => $mediaAsset->id,
    'product_variant_id' => $sourceVariantId,
    'file_path' => 'catalog/test/' . $token . '.jpg',
    'alt_text' => 'Tričko test',
    'sort_order' => 2,
    'is_primary' => 1,
]);
$sourceImageId = (int) $sourceImage->id;
$derivativePath = 'catalog/test/' . $token . '-listing.webp';
$database->table('product_image_derivative')->insert([
    'product_image_id' => $sourceImageId,
    'format' => 'webp',
    'source_sha256' => hash('sha256', 'image-source-' . $token),
    'recipe_sha256' => hash('sha256', 'image-recipe-' . $token),
    'width' => 600,
    'height' => 600,
    'file_path' => $derivativePath,
    'file_size_bytes' => 12345,
]);

$service->archive($sourceId);
Assert::same(ProductStatus::Archived, $repository->findStatus($sourceId));
Assert::same(0, (int) $database->table('product_variant')->get($sourceVariantId)->is_active);

Assert::exception(
    fn () => $service->archive($sourceId),
    InvalidArgumentException::class,
);

$pdo = $database->getConnection()->getPdo();
$terminalStateError = null;
try {
    $pdo->prepare("UPDATE product SET status = 'draft' WHERE id = ?")->execute([$sourceId]);
} catch (PDOException $exception) {
    $terminalStateError = $exception;
}
Assert::type(PDOException::class, $terminalStateError);
Assert::same('45000', (string) $terminalStateError->getCode());
Assert::same('archived', $database->table('product')->get($sourceId)->status);

$copyId = $service->createCopyFromArchived($sourceId);
Assert::true($copyId > 0);
Assert::notSame($sourceId, $copyId);
$copy = $database->table('product')->get($copyId);
Assert::same('draft', $copy->status);
Assert::same('physical', $copy->type);
Assert::same((int) $taxClass->id, (int) $copy->tax_class_id);
Assert::contains('-copy', $copy->code);

$copyTranslation = $database->table('product_translation')
    ->where('product_id', $copyId)
    ->where('locale', 'cs')
    ->fetch();
Assert::notNull($copyTranslation);
Assert::same('Testovací produkt ' . $token, $copyTranslation->name);
Assert::same('<p>Popis</p>', $copyTranslation->description);
Assert::notSame(
    $database->table('product_translation')->where('product_id', $sourceId)->where('locale', 'cs')->fetch()->slug,
    $copyTranslation->slug,
);

$copyVariant = $database->table('product_variant')->where('product_id', $copyId)->fetch();
Assert::notNull($copyVariant);
Assert::notSame($sourceVariantId, (int) $copyVariant->id);
Assert::same(0, (int) $copyVariant->is_active);
Assert::same(1, (int) $copyVariant->is_default);
Assert::null($copyVariant->ean);
Assert::contains('COPY-' . $copyId . '-', $copyVariant->sku);
Assert::same($sourceVariant->combination_hash, $copyVariant->combination_hash);
Assert::same(250, (int) $copyVariant->weight_g);
Assert::same(300, (int) $copyVariant->length_mm);
Assert::same(200, (int) $copyVariant->width_mm);
Assert::same(20, (int) $copyVariant->height_mm);

$copyPrice = $database->table('variant_price')
    ->where('product_variant_id', $copyVariant->id)
    ->where('currency', $currency->code)
    ->fetch();
Assert::notNull($copyPrice);
Assert::same(1299, (int) $copyPrice->amount_minor);
Assert::same(1, (int) $copyPrice->tax_included);

$copyStock = $database->table('stock_item')
    ->where('product_variant_id', $copyVariant->id)
    ->fetch();
Assert::notNull($copyStock);
Assert::same(0, (int) $copyStock->track_stock);
Assert::same(1, (int) $copyStock->allow_backorder);
Assert::same('0', (string) $copyStock->quantity_on_hand);
Assert::same('0', (string) $copyStock->quantity_reserved);
Assert::same(0, $database->table('variant_price_history')
    ->where('product_variant_id', $copyVariant->id)->count('*'));
Assert::same(0, $database->table('stock_movement')
    ->where('product_variant_id', $copyVariant->id)->count('*'));

$copyImage = $database->table('product_image')->where('product_id', $copyId)->fetch();
Assert::notNull($copyImage);
Assert::notSame($sourceImageId, (int) $copyImage->id);
Assert::same((int) $mediaAsset->id, (int) $copyImage->media_asset_id);
Assert::same((int) $copyVariant->id, (int) $copyImage->product_variant_id);
$copyDerivative = $database->table('product_image_derivative')
    ->where('product_image_id', $copyImage->id)->fetch();
Assert::notNull($copyDerivative);
Assert::same($derivativePath, $copyDerivative->file_path);

$draft = $database->table('product')->insert([
    'code' => 'f5-25-draft-' . $token,
    'status' => 'draft',
    'type' => 'physical',
    'tax_class_id' => $taxClass->id,
]);
Assert::exception(
    fn () => $service->createCopyFromArchived((int) $draft->id),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
