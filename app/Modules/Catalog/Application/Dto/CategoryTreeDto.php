<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application\\Dto;

use App\\Modules\\Catalog\\Domain\\Category\\Category;

/**
 * Kategorie včetně překladu požadovaného jazyka a seřazených potomků.
 */
final readonly class CategoryTreeDto
{
    /**
     * @param list<CategoryTreeDto> $children
     */
    public function __construct(
        public Category $category,
        public ?CategoryTranslationDto $translation,
        public array $children,
    ) {}
}
