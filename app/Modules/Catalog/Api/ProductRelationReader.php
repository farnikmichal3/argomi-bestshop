<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductRelationItem;
use App\Modules\Catalog\Api\Dto\ProductRelationOption;
use App\Shared\Domain\Locale\Locale;

interface ProductRelationReader
{
    /** @return list<ProductRelationItem> */
    public function findForProduct(int $productId, Locale $locale): array;

    /** @return list<ProductRelationOption> */
    public function findAvailableProducts(int $productId, Locale $locale): array;
}
