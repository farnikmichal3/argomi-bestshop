<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application;

use App\Modules\Inventory\Application\Dto\ProductAvailabilityDto;
use App\Modules\Inventory\Application\Dto\VariantAvailabilityStatus;
use App\Shared\Domain\Quantity\Quantity;

final readonly class ProductAvailabilityService
{
    public function __construct(
        private ProductAvailabilityRepository $repository,
    ) {}

    /**
     * @throws \InvalidArgumentException Při neplatném ID produktu.
     */
    public function getByProductId(int $productId): ProductAvailabilityDto
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('ID produktu musí být kladné.');
        }

        $variants = $this->repository->findActiveVariantAvailability($productId);
        $trackedAvailable = Quantity::fromInteger(0);
        $untrackedCount = 0;
        $missingCount = 0;
        $hasBackorderVariant = false;

        foreach ($variants as $variant) {
            if ($variant->allowBackorder) {
                $hasBackorderVariant = true;
            }

            if ($variant->status === VariantAvailabilityStatus::Untracked) {
                $untrackedCount++;
                continue;
            }

            if ($variant->status === VariantAvailabilityStatus::MissingStockRecord) {
                $missingCount++;
                continue;
            }

            if ($variant->quantityAvailable !== null) {
                $trackedAvailable = $trackedAvailable->add($variant->quantityAvailable);
            }
        }

        return new ProductAvailabilityDto(
            $productId,
            $variants,
            $trackedAvailable,
            $untrackedCount,
            $missingCount,
            $hasBackorderVariant,
        );
    }
}
