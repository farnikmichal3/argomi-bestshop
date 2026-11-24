<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class ProductEditorConfigurationData
{
    /**
     * @param list<ProductEditorAttributeOption> $availableAttributes
     * @param array<int, int|string|bool> $assignedValues keyed by attribute ID
     * @param list<int> $variantAxisIds
     */
    public function __construct(
        public array $availableAttributes,
        public array $assignedValues,
        public array $variantAxisIds,
    ) {}
}
