<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\Dto\ProductImageData;
use App\Modules\Catalog\Api\ProductImageReader;
use App\Modules\Catalog\Api\ProductImageWriter;
use App\Modules\Media\Api\MediaAssetReader;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

final readonly class ProductImageManagementService implements ProductImageReader, ProductImageWriter
{
    public function __construct(
        private ProductImageRepository $repository,
        private MediaAssetReader $mediaAssets,
        private CatalogPermission $permission,
        private Transaction $transaction,
    ) {}

    public function findForProduct(int $productId): array
    {
        if (!$this->permission->canView()) {
            throw new CatalogAccessDeniedException();
        }
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }

        return array_map(
            static fn($image): ProductImageData => new ProductImageData(
                $image->id,
                $image->mediaAssetId,
                $image->variantId,
                $image->altText,
                $image->sortOrder,
                $image->isPrimary,
                $image->sourceMimeType,
                $image->sourceWidth,
                $image->sourceHeight,
                $image->sourceFileSizeBytes,
            ),
            $this->repository->findForManagement($productId),
        );
    }

    public function assign(
        int $productId,
        ?int $variantId,
        int $mediaAssetId,
        ?string $altText = null,
        int $sortOrder = 0,
        bool $isPrimary = false,
    ): int {
        $this->assertCanManage();
        if ($productId < 1 || ($variantId !== null && $variantId < 1) || $mediaAssetId < 1 || $sortOrder < 0) {
            throw new InvalidArgumentException('Produkt, varianta, médium nebo pořadí nejsou platné.');
        }
        if ($altText !== null && mb_strlen($altText) > 255) {
            throw new InvalidArgumentException('ALT text může mít nejvýše 255 znaků.');
        }

        $asset = $this->mediaAssets->find($mediaAssetId);
        if ($asset === null) {
            throw new InvalidArgumentException('Mediální položka nebyla nalezena.');
        }

        return $this->transaction->run(
            fn(): int => $this->repository->createForProduct(
                $productId,
                $variantId,
                $altText,
                $sortOrder,
                $asset,
                $isPrimary,
            ),
        );
    }

    public function update(int $productId, int $imageId, ?string $altText, int $sortOrder): void
    {
        $this->assertCanManage();
        $this->transaction->run(
            fn() => $this->repository->updateMetadata($productId, $imageId, $altText, $sortOrder),
        );
    }

    public function remove(int $productId, int $imageId): void
    {
        $this->assertCanManage();
        $this->transaction->run(
            fn() => $this->repository->remove($productId, $imageId),
        );
    }

    public function setPrimary(int $productId, int $imageId): void
    {
        $this->assertCanManage();
        $this->transaction->run(
            fn() => $this->repository->setPrimary($productId, $imageId),
        );
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
    }
}
