<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductImageDto
{
    /**
     * @param list<ProductImageDerivativeDto> $derivatives
     */
    public function __construct(
        public int $id,
        public ?int $variantId,
        public string $filePath,
        public ?string $altText,
        public int $sortOrder,
        public ?string $sourceSha256,
        public ?string $sourceMimeType,
        public ?int $sourceWidth,
        public ?int $sourceHeight,
        public ?int $sourceFileSizeBytes,
        public array $derivatives = [],
    ) {}
}
