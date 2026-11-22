<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\CategoryEditorReader;
use App\Modules\Catalog\Api\Dto\CategoryEditorData;
use App\Modules\Catalog\Application\CategoryEditorQueryService;

final class CategoryEditorApiService implements CategoryEditorReader
{
    public function __construct(private readonly CategoryEditorQueryService $queryService) {}

    public function findForEdit(int $categoryId): ?CategoryEditorData
    {
        return $this->queryService->findForEdit($categoryId);
    }
}
