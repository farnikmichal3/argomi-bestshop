<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Shared\Domain\Locale\Locale;

/**
 * Veřejný kontrakt pro čtení stromu kategorií katalogu.
 */
interface CategoryTreeReader
{
    /**
     * @return list<CategoryTreeItem>
     */
    public function findTree(Locale $locale): array;
}
