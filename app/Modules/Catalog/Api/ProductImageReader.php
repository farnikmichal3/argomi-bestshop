<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductImageData;

interface ProductImageReader
{
    /** @return list<ProductImageData> */
    public function findForProduct(int $productId): array;
}
