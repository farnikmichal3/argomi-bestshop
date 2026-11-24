<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface ProductVariantGenerationRepository
{
    public function findProductStatus(int $productId): ?string;

    /**
     * @return array<string, array{attributeId: int, type: string}>
     */
    public function findAxisDefinitions(int $productId): array;

    public function enumValueExists(int $attributeId, string $valueCode): bool;

    public function variantExists(int $productId, string $combinationHash): bool;

    public function nextSortOrder(int $productId): int;

    public function createVariant(int $productId, string $sku, string $combinationHash, int $sortOrder): void;

    public function insertVariantAttributeValue(
        int $productId,
        string $attributeCode,
        int $attributeId,
        int $variantId,
        string $type,
        string|bool $value,
    ): void;
}
