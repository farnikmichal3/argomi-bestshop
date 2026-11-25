<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

interface ProductImageWriter
{
    public function assign(
        int $productId,
        ?int $variantId,
        int $mediaAssetId,
        ?string $altText = null,
        int $sortOrder = 0,
        bool $isPrimary = false,
    ): int;

    public function update(int $productId, int $imageId, ?string $altText, int $sortOrder): void;

    public function remove(int $productId, int $imageId): void;

    public function setPrimary(int $productId, int $imageId): void;
}
