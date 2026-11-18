<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductAttributeDetailDto
{
    public function __construct(
        public int $attributeId,
        public string $code,
        public ?string $name,
        public string $type,
        public ?int $unitId,
        public ?string $value,
        public ?string $label,
    ) {}
}
