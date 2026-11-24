<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductEditorData;

interface ProductEditorReader
{
    public function findForEdit(int $productId): ?ProductEditorData;
}
