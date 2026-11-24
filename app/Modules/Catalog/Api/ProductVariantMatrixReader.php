<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductVariantMatrix;
use App\Shared\Domain\Locale\Locale;

interface ProductVariantMatrixReader
{
    public function findForProduct(int $productId, Locale $locale): ?ProductVariantMatrix;
}
