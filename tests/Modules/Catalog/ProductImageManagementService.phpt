--TEST--
Product image management assigns Media assets and protects metadata, primary state, and removal
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\Dto\ProductImageDto;
use App\Modules\Catalog\Application\ProductImageManagementService;
use App\Modules\Catalog\Application\ProductImageRepository;
use App\Modules\Media\Api\Dto\MediaAssetData;
use App\Modules\Media\Api\MediaAssetReader;
use App\Shared\Application\Transaction\Transaction;
use DateTimeImmutable;
use InvalidArgumentException;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$asset = new MediaAssetData(
    8,
    'sample.png',
    'media/0123456789abcdef/original.bin',
    hash('sha256', 'source'),
    'image/png',
    32,
    24,
    512,
    new DateTimeImmutable('2026-11-25T00:00:00+00:00'),
    [],
);
$image = new ProductImageDto(
    51,
    8,
    null,
    'Product image',
    4,
    true,
    'image/png',
    32,
    24,
    512,
);

$repository = new class($image) implements ProductImageRepository {
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function __construct(private ProductImageDto $image) {}

    public function findForProduct(int $productId, array $activeVariantIds): array
    {
        return [];
    }

    public function findForManagement(int $productId): array
    {
        return [$this->image];
    }

    public function createForProduct(
        int $productId,
        ?int $variantId,
        ?string $altText,
        int $sortOrder,
        MediaAssetData $asset,
        bool $isPrimary = false,
    ): int {
        $this->calls[] = [
            'method' => 'assign',
            'productId' => $productId,
            'variantId' => $variantId,
            'altText' => $altText,
            'sortOrder' => $sortOrder,
            'mediaAssetId' => $asset->id,
            'isPrimary' => $isPrimary,
        ];
        return 51;
    }

    public function updateMetadata(int $productId, int $imageId, ?string $altText, int $sortOrder): void
    {
        $this->calls[] = ['method' => 'update', 'productId' => $productId, 'imageId' => $imageId, 'altText' => $altText, 'sortOrder' => $sortOrder];
    }

    public function remove(int $productId, int $imageId): void
    {
        $this->calls[] = ['method' => 'remove', 'productId' => $productId, 'imageId' => $imageId];
    }

    public function setPrimary(int $productId, int $imageId): void
    {
        $this->calls[] = ['method' => 'primary', 'productId' => $productId, 'imageId' => $imageId];
    }
};

$mediaReader = new class($asset) implements MediaAssetReader {
    public function __construct(private MediaAssetData $asset) {}

    public function find(int $id): ?MediaAssetData
    {
        return $id === $this->asset->id ? $this->asset : null;
    }

    public function findLibrary(int $limit = 48, int $offset = 0): array
    {
        return [$this->asset];
    }
};

$permission = new class implements CatalogPermission {
    public function canView(): bool { return true; }
    public function canManage(): bool { return true; }
};
$transaction = new class implements Transaction {
    public function run(callable $operation): mixed { return $operation(); }
};

$service = new ProductImageManagementService($repository, $mediaReader, $permission, $transaction);
Assert::same(51, $service->assign(12, 44, 8, 'Variant blue', 3, true));
Assert::same([
    'method' => 'assign',
    'productId' => 12,
    'variantId' => 44,
    'altText' => 'Variant blue',
    'sortOrder' => 3,
    'mediaAssetId' => 8,
    'isPrimary' => true,
], $repository->calls[0]);

$images = $service->findForProduct(12);
Assert::same(1, count($images));
Assert::same(8, $images[0]->mediaAssetId);
Assert::same(true, $images[0]->isPrimary);

$service->update(12, 51, 'Updated ALT', 5);
$service->setPrimary(12, 51);
$service->remove(12, 51);
Assert::same('update', $repository->calls[1]['method']);
Assert::same('primary', $repository->calls[2]['method']);
Assert::same('remove', $repository->calls[3]['method']);

Assert::exception(
    static fn () => $service->assign(12, null, 99),
    InvalidArgumentException::class,
);

$denied = new class implements CatalogPermission {
    public function canView(): bool { return false; }
    public function canManage(): bool { return false; }
};
$deniedService = new ProductImageManagementService($repository, $mediaReader, $denied, $transaction);
Assert::exception(
    static fn () => $deniedService->assign(12, null, 8),
    CatalogAccessDeniedException::class,
);
Assert::exception(
    static fn () => $deniedService->findForProduct(12),
    CatalogAccessDeniedException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
