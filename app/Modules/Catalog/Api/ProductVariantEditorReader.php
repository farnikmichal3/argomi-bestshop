<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductVariantEditorData;
use App\Shared\Domain\Locale\Locale;

interface ProductVariantEditorReader
{
    public function findForEdit(int $variantId, Locale $locale): ?ProductVariantEditorData;
}
