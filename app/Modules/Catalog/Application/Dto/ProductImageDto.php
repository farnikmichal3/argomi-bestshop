<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductImageDto
{
    public function __construct(
        public int $id,
        public ?int $variantId,
        public string $filePath,
        public ?string $altText,
        public int $sortOrder,
    ) {}
}
