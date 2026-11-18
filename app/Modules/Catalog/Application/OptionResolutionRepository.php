<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\CustomerOptionDefinitionDto;

interface OptionResolutionRepository
{
    /**
     * Načte definice voleb a povolené hodnoty aktivního produktu dávkově.
     *
     * @return list<CustomerOptionDefinitionDto>|null Null pro neexistující nebo neaktivní produkt.
     */
    public function findActiveOptionDefinitions(int $productId): ?array;
}
