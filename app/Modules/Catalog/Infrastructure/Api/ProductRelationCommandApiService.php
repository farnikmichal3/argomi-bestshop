<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\Dto\ProductRelationOrder;
use App\Modules\Catalog\Api\ProductRelationKind;
use App\Modules\Catalog\Api\ProductRelationWriter;
use App\Modules\Catalog\Application\Dto\ProductRelationOrderDto;
use App\Modules\Catalog\Application\ProductRelationService;
use App\Modules\Catalog\Domain\ProductRelationType;

final readonly class ProductRelationCommandApiService implements ProductRelationWriter
{
    public function __construct(private ProductRelationService $relations) {}

    public function add(int $productId, int $relatedProductId, ProductRelationKind $type, int $sortOrder = 0): void
    {
        $this->relations->add($productId, $relatedProductId, ProductRelationType::from($type->value), $sortOrder);
    }

    public function remove(int $productId, int $relatedProductId, ProductRelationKind $type): bool
    {
        return $this->relations->remove($productId, $relatedProductId, ProductRelationType::from($type->value));
    }

    public function reorder(int $productId, array $orderedRelations): void
    {
        $relations = [];
        foreach ($orderedRelations as $relation) {
            $relations[] = new ProductRelationOrderDto(
                $relation->relatedProductId,
                ProductRelationType::from($relation->type->value),
            );
        }

        $this->relations->reorder($productId, $relations);
    }
}
