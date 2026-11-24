<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Shared\Domain\Locale\Locale;

interface ProductEditorConfigurationRepository
{
    /**
     * @return list<array{id:int,code:string,type:string,name:?string,unitName:?string,unitSymbol:?string,values:list<array{id:int,code:string,label:?string}>}>
     */
    public function findAvailableAttributes(Locale $locale): array;

    /** @return array<int, int|string|bool> */
    public function findAssignedValues(int $productId): array;

    /** @return list<int> */
    public function findVariantAxisIds(int $productId): array;

    public function productExists(int $productId): bool;
}
