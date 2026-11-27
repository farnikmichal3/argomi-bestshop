<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductRelationOrder;

interface ProductRelationWriter
{
    public function add(int $productId, int $relatedProductId, ProductRelationKind $type, int $sortOrder = 0): void;

    public function remove(int $productId, int $relatedProductId, ProductRelationKind $type): bool;

    /** @param list<ProductRelationOrder> $orderedRelations */
    public function reorder(int $productId, array $orderedRelations): void;
}
