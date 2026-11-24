<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;



interface AttributeWriter
{
    /** @param array<string, mixed> $translations */
    public function create(string $code, AttributeDataType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): int;

    /** @param array<string, mixed> $translations */
    public function update(int $attributeId, string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): void;

    public function delete(int $attributeId): void;

    /** @param array<string, mixed> $translations */
    public function createValue(int $attributeId, string $code, int $sortOrder, array $translations): int;

    /** @param array<string, mixed> $translations */
    public function updateValue(int $attributeId, int $valueId, string $code, int $sortOrder, array $translations): void;

    public function deleteValue(int $attributeId, int $valueId): void;
}
