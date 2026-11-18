<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application\\Dto;

use App\\Shared\\Domain\\Locale\\Locale;

/**
 * Neměnný lokalizovaný obsah kategorie katalogu.
 */
final readonly class CategoryTranslationDto
{
    public function __construct(
        public int $categoryId,
        public Locale $locale,
        public string $name,
        public string $slug,
        public ?string $description,
    ) {}
}
