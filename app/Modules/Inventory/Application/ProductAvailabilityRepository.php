<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application;

use App\Modules\Inventory\Application\Dto\VariantAvailabilityDto;

interface ProductAvailabilityRepository
{
    /**
     * @return list<VariantAvailabilityDto>
     */
    public function findActiveVariantAvailability(int $productId): array;
}
