<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductRelationDto;
use App\Shared\Domain\Locale\Locale;

/**
 * Aplikační kontrakt pro čtení souvisejících produktů katalogu.
 */
interface ProductRelationRepository
{
    /**
     * Vrátí související aktivní produkty v pořadí určeném katalogem.
     *
     * Vztahy jsou směrové; opačný vztah se načítá samostatně.
     *
     * @return list<ProductRelationDto>
     */
    public function findForProduct(int $productId, Locale $locale): array;
}
