<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductImageDto;
use App\Modules\Media\Api\Dto\MediaAssetData;

interface ProductImageRepository
{
    /**
     * Načte obrázky produktu a pouze těch variant, které jsou součástí detailu.
     *
     * @param list<int> $activeVariantIds
     * @return list<ProductImageDto>
     */
    public function findForProduct(int $productId, array $activeVariantIds): array;

    /** @return list<ProductImageDto> */
    public function findForManagement(int $productId): array;

    /**
     * Zapíše vazbu originálu na produkt/variantu a metadata všech odvozených souborů.
     *
     * Volající musí metodu spustit uvnitř aplikační databázové transakce.
     */
    public function createForProduct(
        int $productId,
        ?int $variantId,
        ?string $altText,
        int $sortOrder,
        MediaAssetData $asset,
        bool $isPrimary = false,
    ): int;

    public function updateMetadata(int $productId, int $imageId, ?string $altText, int $sortOrder): void;

    public function remove(int $productId, int $imageId): void;

    public function setPrimary(int $productId, int $imageId): void;
}
