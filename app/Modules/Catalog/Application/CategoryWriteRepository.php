<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

use App\\Shared\\Domain\\Locale\\Locale;

interface CategoryWriteRepository
{
    public function isCodeAvailable(string $code, ?int $excludeCategoryId = null): bool;
    public function parentExists(int $categoryId): bool;
    public function findParentId(int $categoryId): ?int;
    public function createCategory(?int $parentId, string $code, int $sortOrder, bool $isActive): int;
    public function updateCategory(int $categoryId, ?int $parentId, string $code, int $sortOrder, bool $isActive): void;
    public function saveTranslation(int $categoryId, Locale $locale, string $name, string $slug, ?string $description): void;
}
