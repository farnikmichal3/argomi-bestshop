<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

/**
 * Kompletní lokalizovaný read model aktivního produktu pro katalog.
 *
 * DTO záměrně obsahuje pouze data potřebná pro zobrazení detailu.
 */
final readonly class ProductDetailDto
{
    /**
     * @param list<ProductVariantDetailDto> $variants
     * @param list<ProductImageDto> $images
     * @param list<ProductAttributeDetailDto> $attributes
     * @param list<CustomerOptionDetailDto> $options
     */
    public function __construct(
        public ProductDto $product,
        public ?ProductTranslationDto $translation,
        public array $variants,
        public array $images,
        public array $attributes,
        public array $options,
    ) {}
}
