<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application\\Dto;

/**
 * Neměnný výsledek stránkovaného výpisu produktů kategorie.
 *
 * @phpstan-type ProductList list<ProductListItemDto>
 */
final readonly class ProductListingDto
{
    /**
     * @param list<ProductListItemDto> $items
     */
    public function __construct(
        public array $items,
        public int $totalItems,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}
}
