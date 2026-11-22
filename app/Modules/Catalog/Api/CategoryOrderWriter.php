<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Veřejné API pro změnu pořadí přímých sourozenců v kategoriovém stromu.
 */
interface CategoryOrderWriter
{
    /** @param array<mixed> $orderedCategoryIds Úplný seznam sourozenců v požadovaném pořadí. */
    public function reorder(?int $parentId, array $orderedCategoryIds): void;
}
