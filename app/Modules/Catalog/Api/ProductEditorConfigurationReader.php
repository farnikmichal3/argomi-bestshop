<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductEditorConfigurationData;
use App\Shared\Domain\Locale\Locale;

interface ProductEditorConfigurationReader
{
    public function findForProduct(int $productId, Locale $locale): ?ProductEditorConfigurationData;
}
