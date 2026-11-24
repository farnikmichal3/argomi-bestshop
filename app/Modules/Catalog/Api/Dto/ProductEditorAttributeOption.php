<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Api\AttributeDataType;

final readonly class ProductEditorAttributeOption
{
    /**
     * @param list<ProductEditorAttributeValueOption> $values
     */
    public function __construct(
        public int $id,
        public string $code,
        public AttributeDataType $type,
        public string $name,
        public ?string $unitName,
        public ?string $unitSymbol,
        public array $values,
    ) {}
}
