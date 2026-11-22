<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Api\\Dto;

use App\\Modules\\Catalog\\Domain\\Product\\ProductStatus;
use App\\Modules\\Catalog\\Domain\\Product\\ProductType;
use InvalidArgumentException;

/**
 * Neměnné filtry administrátorského výpisu produktů.
 */
final readonly class ProductAdminListFilter
{
    public function __construct(
        public ?int $categoryId = null,
        public ?ProductStatus $status = null,
        public ?ProductType $type = null,
        public int $page = 1,
        public int $perPage = 12,
    ) {
        if ($categoryId !== null && $categoryId < 1) {
            throw new InvalidArgumentException('ID kategorie musí být kladné.');
        }

        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Stránkování musí být kladné; limit stránky je 100.');
        }

        if ($page > intdiv(PHP_INT_MAX, $perPage)) {
            throw new InvalidArgumentException('Požadovaná stránka překračuje bezpečný rozsah offsetu.');
        }
    }
}
