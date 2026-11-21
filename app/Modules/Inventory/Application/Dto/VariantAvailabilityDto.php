<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Dto;

use App\Shared\Domain\Quantity\Quantity;

final readonly class VariantAvailabilityDto
{
    public function __construct(
        public int $variantId,
        public string $sku,
        public VariantAvailabilityStatus $status,
        public ?Quantity $quantityOnHand,
        public ?Quantity $quantityReserved,
        public ?Quantity $quantityAvailable,
        public bool $allowBackorder,
    ) {}
}
