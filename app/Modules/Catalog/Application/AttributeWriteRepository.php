<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Attribute\AttributeType;

interface AttributeWriteRepository
{
    public function isCodeAvailable(string $code, ?int $excludeAttributeId = null): bool;
    public function unitExists(int $unitId): bool;
    public function attributeExists(int $attributeId): bool;
    public function findAttributeType(int $attributeId): ?AttributeType;
    public function hasAttributeAxes(int $attributeId): bool;
    public function hasAttributeValues(int $attributeId): bool;
    public function createAttribute(string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder): int;
    public function updateAttribute(int $attributeId, string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder): void;
    public function deleteAttribute(int $attributeId): void;
    public function isValueCodeAvailable(int $attributeId, string $code, ?int $excludeValueId = null): bool;
    public function valueExists(int $attributeId, int $valueId): bool;
    public function isValueUsed(int $attributeId, int $valueId): bool;
    public function createValue(int $attributeId, string $code, int $sortOrder): int;
    public function updateValue(int $attributeId, int $valueId, string $code, int $sortOrder): void;
    public function deleteValue(int $attributeId, int $valueId): void;
    public function saveAttributeTranslation(int $attributeId, string $locale, string $name): void;
    public function saveValueTranslation(int $valueId, string $locale, string $label): void;
}
