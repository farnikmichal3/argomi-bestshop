<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Domain\Attribute\AttributeType;

final readonly class AttributeEditorData
{
    /**
     * @param array<string, string> $translations
     * @param list<AttributeValueEditorData> $values
     */
    public function __construct(
        public int $id,
        public string $code,
        public AttributeType $type,
        public ?int $unitId,
        public bool $isFilterable,
        public int $sortOrder,
        public array $translations,
        public array $values,
        public bool $isUsed,
    ) {}
}
