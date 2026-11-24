<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class ProductVariantEditorData
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $productCode,
        public ?string $productName,
        public string $sku,
        public ?string $ean,
        public ?int $weightG,
        public bool $isDefault,
        public bool $isActive,
    ) {}
}
