<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Application\CategoryManagementService;
use App\Shared\Domain\Locale\Locale;

/**
 * Převádí interní strom kategorií na stabilní veřejné API modulu.
 */
final class CategoryTreeQueryService implements CategoryTreeReader
{
    public function __construct(
        private readonly CategoryManagementService $categoryManagement,
    ) {}

    public function findTree(Locale $locale): array
    {
        return $this->mapNodes($this->categoryManagement->findTree($locale));
    }

    /**
     * @param list<\App\Modules\Catalog\Application\Dto\CategoryTreeDto> $nodes
     * @return list<CategoryTreeItem>
     */
    private function mapNodes(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $result[] = new CategoryTreeItem(
                $node->category->id,
                $node->category->code,
                $node->translation?->name,
                $node->category->isActive,
                $this->mapNodes($node->children),
            );
        }

        return $result;
    }
}
