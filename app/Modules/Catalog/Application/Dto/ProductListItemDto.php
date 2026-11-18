<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application\\Dto;

/**
 * Jeden produkt v lokalizovaném výpisu kategorie.
 *
 * Překlad může chybět; produkt zůstává ve výsledku a prezentační vrstva
 * může bezpečně rozhodnout, jak chybějící jazyk zobrazit.
 */
final readonly class ProductListItemDto
{
    public function __construct(
        public ProductDto $product,
        public ?ProductTranslationDto $translation,
    ) {}
}
