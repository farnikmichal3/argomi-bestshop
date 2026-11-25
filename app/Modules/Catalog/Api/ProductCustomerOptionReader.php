<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\ProductCustomerOptionConfigurationData;

interface ProductCustomerOptionReader
{
    public function findForProduct(int $productId): ?ProductCustomerOptionConfigurationData;
}
