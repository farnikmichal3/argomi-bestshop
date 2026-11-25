<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class ProductImageData
{
    public function __construct(
        public int $id,
        public int $mediaAssetId,
        public ?int $variantId,
        public ?string $altText,
        public int $sortOrder,
        public bool $isPrimary,
        public ?string $sourceMimeType,
        public ?int $sourceWidth,
        public ?int $sourceHeight,
        public ?int $sourceFileSizeBytes,
    ) {}
}
