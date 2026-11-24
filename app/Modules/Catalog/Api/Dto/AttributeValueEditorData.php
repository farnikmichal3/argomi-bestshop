<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class AttributeValueEditorData
{
    /** @param array<string, string> $translations */
    public function __construct(
        public int $id,
        public string $code,
        public int $sortOrder,
        public array $translations,
    ) {}
}
