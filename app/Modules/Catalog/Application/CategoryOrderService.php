<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

/**
 * Use-case pro atomické přeřazení všech přímých sourozenců kategorie.
 */
final class CategoryOrderService
{
    public function __construct(
        private readonly CategoryWriteRepository $repository,
        private readonly Transaction $transaction,
        private readonly CatalogPermission $permission,
    ) {}

    /** @param list<int> $orderedCategoryIds */
    public function reorder(?int $parentId, array $orderedCategoryIds): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($parentId !== null && ($parentId < 1 || !$this->repository->parentExists($parentId))) {
            throw new InvalidArgumentException('Rodičovská kategorie nebyla nalezena.');
        }
        if (!array_is_list($orderedCategoryIds)) {
            throw new InvalidArgumentException('Pořadí kategorií musí být seznam.');
        }

        $seen = [];
        foreach ($orderedCategoryIds as $categoryId) {
            if (!is_int($categoryId) || $categoryId < 1 || isset($seen[$categoryId])) {
                throw new InvalidArgumentException('Pořadí obsahuje neplatné nebo duplicitní ID kategorie.');
            }
            $seen[$categoryId] = true;
        }

        $this->transaction->run(function () use ($parentId, $orderedCategoryIds): void {
            $currentIds = $this->repository->findSiblingIds($parentId);
            $expected = $currentIds;
            $provided = $orderedCategoryIds;
            sort($expected);
            sort($provided);
            if ($expected !== $provided) {
                throw new InvalidArgumentException('Pořadí musí obsahovat všechny přímé sourozence právě jednou.');
            }

            foreach ($orderedCategoryIds as $sortOrder => $categoryId) {
                $this->repository->updateSortOrder($categoryId, $sortOrder);
            }
        });
    }
}
