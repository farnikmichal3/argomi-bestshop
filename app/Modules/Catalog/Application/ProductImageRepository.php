<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

use App\\Modules\\Catalog\\Application\\Dto\\ProductImageDto;

interface ProductImageRepository
{
    /**
     * Načte obrázky produktu a pouze těch variant, které jsou součástí detailu.
     *
     * @param list<int> $activeVariantIds
     * @return list<ProductImageDto>
     */
    public function findForProduct(int $productId, array $activeVariantIds): array;
}
