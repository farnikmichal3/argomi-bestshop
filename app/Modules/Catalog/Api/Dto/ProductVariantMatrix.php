<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class ProductVariantMatrix
{
    /**
     * @param list<VariantMatrixAxis> $axes
     * @param list<VariantMatrixCombination> $combinations
     */
    public function __construct(
        public int $productId,
        public string $productCode,
        public ?string $productName,
        public array $axes,
        public array $combinations,
    ) {}
}
