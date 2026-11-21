<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Dto;

use App\Shared\Domain\Quantity\Quantity;

/**
 * @param list<VariantAvailabilityDto> $variants
 */
final readonly class ProductAvailabilityDto
{
    public function __construct(
        public int $productId,
        public array $variants,
        public Quantity $trackedQuantityAvailable,
        public int $untrackedVariantCount,
        public int $missingStockRecordCount,
        public bool $hasBackorderVariant,
    ) {}
}
