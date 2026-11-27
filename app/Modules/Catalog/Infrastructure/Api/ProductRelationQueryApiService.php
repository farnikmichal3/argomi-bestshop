<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\Dto\ProductRelationItem;
use App\Modules\Catalog\Api\Dto\ProductRelationOption;
use App\Modules\Catalog\Api\ProductRelationKind;
use App\Modules\Catalog\Api\ProductRelationReader;
use App\Modules\Catalog\Application\ProductRelationRepository;
use App\Shared\Domain\Locale\Locale;

final readonly class ProductRelationQueryApiService implements ProductRelationReader
{
    public function __construct(private ProductRelationRepository $relations) {}

    public function findForProduct(int $productId, Locale $locale): array
    {
        $result = [];
        foreach ($this->relations->findForProduct($productId, $locale) as $relation) {
            $result[] = new ProductRelationItem(
                $relation->productId,
                $relation->productCode,
                $relation->translation?->name,
                ProductRelationKind::from($relation->type),
                $relation->sortOrder,
            );
        }

        return $result;
    }

    public function findAvailableProducts(int $productId, Locale $locale): array
    {
        $result = [];
        foreach ($this->relations->findAvailableProducts($productId, $locale) as $product) {
            $result[] = new ProductRelationOption($product->id, $product->code, $product->name);
        }

        return $result;
    }
}
