<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Api\ProductRelationKind;

final readonly class ProductRelationOrder
{
    public function __construct(
        public int $relatedProductId,
        public ProductRelationKind $type,
    ) {}
}
