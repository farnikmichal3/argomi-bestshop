<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

use App\Modules\Catalog\Domain\ProductRelationType;

/**
 * Jedna položka úplného požadovaného pořadí vztahů produktu.
 */
final readonly class ProductRelationOrderDto
{
    public function __construct(
        public int $relatedProductId,
        public ProductRelationType $type,
    ) {}
}
