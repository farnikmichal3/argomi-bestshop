<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductVariantDetailDto
{
    /**
     * @param list<VariantAttributeValueDto> $attributeValues
     */
    public function __construct(
        public int $id,
        public string $sku,
        public bool $isDefault,
        public ?int $weightG,
        public ?int $lengthMm,
        public ?int $widthMm,
        public ?int $heightMm,
        public int $sortOrder,
        public array $attributeValues,
    ) {}
}
