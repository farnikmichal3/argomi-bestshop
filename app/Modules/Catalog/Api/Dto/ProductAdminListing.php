<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

/**
 * Neměnný stránkovaný výsledek administrátorského výpisu produktů.
 */
final readonly class ProductAdminListing
{
    /**
     * @param list<ProductAdminListItem> $items
     */
    public function __construct(
        public array $items,
        public int $totalItems,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}
}
