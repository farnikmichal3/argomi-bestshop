<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Api\ProductRelationKind;

final readonly class ProductRelationItem
{
    public function __construct(
        public int $productId,
        public string $productCode,
        public ?string $name,
        public ProductRelationKind $type,
        public int $sortOrder,
    ) {}
}
