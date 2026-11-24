<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

interface ProductConfigurationWriter
{
    /**
     * @param list<array{attributeId:int, value:int|string|bool}> $attributes
     * @param list<int> $variantAxisIds
     */
    public function save(int $productId, array $attributes, array $variantAxisIds): void;
}
