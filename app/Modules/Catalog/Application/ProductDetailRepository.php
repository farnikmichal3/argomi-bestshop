<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductDetailDto;
use App\Shared\Domain\Locale\Locale;

interface ProductDetailRepository
{
    /**
     * Načte kompletní detail aktivního produktu pro požadovaný jazyk.
     *
     * @return ProductDetailDto|null Null pro neexistující nebo neaktivní produkt.
     */
    public function findActiveDetail(int $productId, Locale $locale): ?ProductDetailDto;
}
