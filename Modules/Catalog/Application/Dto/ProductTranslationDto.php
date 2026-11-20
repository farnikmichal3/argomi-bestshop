<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

use App\Shared\Domain\Locale\Locale;

/**
 * Neměnný přenosový objekt jazykového obsahu katalogového produktu.
 *
 * Locale používá společný hodnotový objekt, aby se jazykový identifikátor
 * v dalších vrstvách nepředával jako neověřený řetězec.
 */
final readonly class ProductTranslationDto
{
    public function __construct(
        public int $id,
        public int $productId,
        public Locale $locale,
        public string $name,
        public string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}
}
