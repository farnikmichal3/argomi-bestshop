<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

interface ProductVariantWriter
{
    public function update(
        int $variantId,
        string $sku,
        ?string $ean,
        ?int $weightG,
        bool $isDefault,
    ): void;
}
