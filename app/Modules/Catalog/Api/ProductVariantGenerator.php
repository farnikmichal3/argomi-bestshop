<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

interface ProductVariantGenerator
{
    /**
     * Vytvoří pouze dosud neexistující kombinace.
     *
     * @param list<array<string, array{type: string, value: string|bool}>> $combinations
     * @return int Počet nově vytvořených variant.
     */
    public function generate(int $productId, array $combinations): int;
}
