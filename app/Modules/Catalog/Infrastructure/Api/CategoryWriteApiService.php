<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\CategoryWriter;
use App\Modules\Catalog\Application\CategoryWriteService;

/**
 * Adaptér veřejného API na interní aplikační službu zápisu kategorií.
 */
final class CategoryWriteApiService implements CategoryWriter
{
    public function __construct(
        private readonly CategoryWriteService $categoryWrite,
    ) {}

    public function create(
        ?int $parentId,
        string $code,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): int {
        return $this->categoryWrite->create($parentId, $code, $sortOrder, $isActive, $translations);
    }

    public function update(
        int $categoryId,
        ?int $parentId,
        string $code,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): void {
        $this->categoryWrite->update($categoryId, $parentId, $code, $sortOrder, $isActive, $translations);
    }
}
