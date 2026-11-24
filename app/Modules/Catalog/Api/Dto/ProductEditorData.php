<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

/**
 * Public, persistence-independent data for the basic product editor.
 */
final readonly class ProductEditorData
{
    /**
     * @param array<string, array{name: string, shortDescription: ?string, description: ?string, metaTitle: ?string, metaDescription: ?string}> $translations
     * @param list<int> $categoryIds
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $status,
        public string $type,
        public int $taxClassId,
        public array $translations,
        public array $categoryIds,
    ) {}
}
