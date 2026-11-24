<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class VariantMatrixAxis
{
    /**
     * @param list<VariantMatrixValue> $values
     */
    public function __construct(
        public int $attributeId,
        public string $code,
        public ?string $name,
        public string $type,
        public array $values,
    ) {}
}
