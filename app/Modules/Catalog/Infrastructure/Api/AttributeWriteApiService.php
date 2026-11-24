<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\AttributeWriter;
use App\Modules\Catalog\Application\AttributeWriteService;
use App\Modules\Catalog\Api\AttributeDataType;

final readonly class AttributeWriteApiService implements AttributeWriter
{
    public function __construct(private AttributeWriteService $writeService) {}

    public function create(string $code, AttributeDataType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): int
    {
        return $this->writeService->create($code, $type, $unitId, $isFilterable, $sortOrder, $translations);
    }

    public function update(int $attributeId, string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): void
    {
        $this->writeService->update($attributeId, $code, $type, $unitId, $isFilterable, $sortOrder, $translations);
    }

    public function delete(int $attributeId): void
    {
        $this->writeService->delete($attributeId);
    }

    public function createValue(int $attributeId, string $code, int $sortOrder, array $translations): int
    {
        return $this->writeService->createValue($attributeId, $code, $sortOrder, $translations);
    }

    public function updateValue(int $attributeId, int $valueId, string $code, int $sortOrder, array $translations): void
    {
        $this->writeService->updateValue($attributeId, $valueId, $code, $sortOrder, $translations);
    }

    public function deleteValue(int $attributeId, int $valueId): void
    {
        $this->writeService->deleteValue($attributeId, $valueId);
    }
}
