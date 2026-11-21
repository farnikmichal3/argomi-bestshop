<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Dto;

use App\Shared\Domain\Quantity\Quantity;

final readonly class ProductAvailabilityDto
{
    /**
     * @param list<VariantAvailabilityDto> $variants
     */
    public function __construct(
        public int $productId,
        public array $variants,
        public Quantity $trackedQuantityAvailable,
        public int $untrackedVariantCount,
        public int $missingStockRecordCount,
        public bool $hasBackorderVariant,
    ) {}
}
