<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface ProductConfigurationRepository
{
    public function productExists(int $productId): bool;

    /** @return array{id:int,type:string}|null */
    public function findAttributeDefinition(int $attributeId): ?array;

    public function enumValueBelongsToAttribute(int $attributeId, int $valueId): bool;

    public function hasVariants(int $productId): bool;

    /** @return list<int> */
    public function findVariantAxisIds(int $productId): array;

    /**
     * @param list<array{attributeId:int, type:string, value:int|string|bool}> $attributes
     */
    public function replaceProductAttributes(int $productId, array $attributes): void;

    /** @param list<int> $attributeIds */
    public function replaceVariantAxes(int $productId, array $attributeIds): void;
}
