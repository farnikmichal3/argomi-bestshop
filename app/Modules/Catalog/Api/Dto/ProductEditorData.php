<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Application\Dto\ProductDto;
use App\Modules\Catalog\Application\Dto\ProductTranslationDto;

/**
 * Data required to populate the basic product editor.
 */
final readonly class ProductEditorData
{
    /**
     * @param list<ProductTranslationDto> $translations
     * @param list<int> $categoryIds
     */
    public function __construct(
        public ProductDto $product,
        public array $translations,
        public array $categoryIds,
    ) {}
}
