<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\CategoryOrderWriter;
use App\Modules\Catalog\Application\CategoryOrderService;

/**
 * Adaptér veřejného API na aplikační službu pořadí kategorií.
 */
final class CategoryOrderApiService implements CategoryOrderWriter
{
    public function __construct(
        private readonly CategoryOrderService $categoryOrder,
    ) {}

    public function reorder(?int $parentId, array $orderedCategoryIds): void
    {
        $this->categoryOrder->reorder($parentId, $orderedCategoryIds);
    }
}
