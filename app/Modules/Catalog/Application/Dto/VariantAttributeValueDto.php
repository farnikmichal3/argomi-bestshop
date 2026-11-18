<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application\\Dto;

final readonly class VariantAttributeValueDto
{
    public function __construct(
        public int $attributeId,
        public string $code,
        public ?string $name,
        public string $type,
        public ?string $value,
        public ?string $label,
    ) {}
}
