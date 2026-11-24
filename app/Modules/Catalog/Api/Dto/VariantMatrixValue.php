<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class VariantMatrixValue
{
    public function __construct(
        public string|bool $value,
        public ?string $label,
        public ?int $attributeValueId = null,
    ) {}
}
