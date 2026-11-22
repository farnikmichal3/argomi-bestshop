<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface CategoryEditorRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findCategory(int $categoryId): ?array;

    /**
     * @return list<array{locale: string, name: string, description: string|null}>
     */
    public function findTranslations(int $categoryId): array;
}
