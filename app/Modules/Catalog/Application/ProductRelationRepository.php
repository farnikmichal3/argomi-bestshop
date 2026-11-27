<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductRelationDto;
use App\Modules\Catalog\Application\Dto\ProductRelationOrderDto;
use App\Modules\Catalog\Domain\ProductRelationType;
use App\Shared\Domain\Locale\Locale;

/**
 * Aplikační kontrakt pro čtení a správu souvisejících produktů katalogu.
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

    /**
     * Vrátí aktivní produkty vhodné pro nový vztah, kromě zdrojového produktu.
     *
     * @return list<\App\Modules\Catalog\Application\Dto\ProductRelationOptionDto>
     */
    public function findAvailableProducts(int $productId, Locale $locale): array;

    /**
     * Vloží směrový vztah. Volající musí zajistit transakci.
     */
    public function add(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
        int $sortOrder,
    ): void;

    /**
     * Odebere pouze přesně určený směrový vztah.
     *
     * @return bool True, pokud byl vztah odebrán.
     */
    public function remove(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
    ): bool;

    /**
     * Nastaví úplné pořadí všech vztahů jednoho zdrojového produktu.
     *
     * @param list<ProductRelationOrderDto> $orderedRelations
     * @throws \DomainException Pokud seznam neobsahuje přesně existující vztahy.
     */
    public function reorder(int $productId, array $orderedRelations): void;
}
