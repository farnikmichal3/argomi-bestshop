<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

/**
 * Lokalizovaný vztah aktivního produktu k jinému aktivnímu produktu.
 */
final readonly class ProductRelationDto
{
    public function __construct(
        public int $productId,
        public string $productCode,
        public string $type,
        public int $sortOrder,
        public ?ProductTranslationDto $translation,
    ) {}
}
