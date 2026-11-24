<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class AttributeAdminItem
{
    public function __construct(
        public int $id,
        public string $code,
        public string $type,
        public ?string $name,
        public ?int $unitId,
        public bool $isFilterable,
        public int $sortOrder,
        public int $valueCount,
        public int $axisCount,
    ) {}
}
