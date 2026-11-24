<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface ProductVariantWriteRepository
{
    public function variantExists(int $variantId): bool;

    public function findProductId(int $variantId): ?int;

    public function isVariantActive(int $variantId): bool;

    public function isSkuAvailable(string $sku, int $excludeVariantId): bool;

    public function isEanAvailable(string $ean, int $excludeVariantId): bool;

    public function updateVariant(int $variantId, string $sku, ?string $ean, ?int $weightG): void;

    public function clearDefaultVariant(int $productId): void;

    public function setDefaultVariant(int $variantId): void;

    public function unsetDefaultVariant(int $variantId): void;
}
