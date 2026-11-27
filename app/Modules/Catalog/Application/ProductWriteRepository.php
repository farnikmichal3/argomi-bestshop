<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Shared\Domain\Locale\Locale;

interface ProductWriteRepository
{
    public function isCodeAvailable(string $code, ?int $excludeProductId = null): bool;

    public function productExists(int $productId): bool;

    /** Produkt má alespoň jednu aktivní variantu a právě jednu z nich označenou jako výchozí. */
    public function hasPublishableVariantConfiguration(int $productId): bool;

    public function taxClassExists(int $taxClassId): bool;

    public function categoryExists(int $categoryId): bool;

    public function createProduct(string $code, ProductStatus $status, ProductType $type, int $taxClassId): int;

    public function updateProduct(int $productId, string $code, ProductStatus $status, ProductType $type, int $taxClassId): void;

    public function saveTranslation(
        int $productId,
        Locale $locale,
        string $name,
        string $slug,
        ?string $shortDescription,
        ?string $description,
        ?string $metaTitle,
        ?string $metaDescription,
    ): void;

    /** @param list<int> $categoryIds */
    public function replaceCategories(int $productId, array $categoryIds): void;
}
