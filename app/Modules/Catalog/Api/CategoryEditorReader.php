<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\CategoryEditorData;

/**
 * Veřejné API pro načtení kompletních dat kategorie v administrátorském editoru.
 */
interface CategoryEditorReader
{
    public function findForEdit(int $categoryId): ?CategoryEditorData;
}
