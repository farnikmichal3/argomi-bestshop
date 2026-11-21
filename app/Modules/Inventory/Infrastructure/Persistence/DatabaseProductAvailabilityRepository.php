<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Modules\Inventory\Application\Dto\VariantAvailabilityDto;
use App\Modules\Inventory\Application\Dto\VariantAvailabilityStatus;
use App\Modules\Inventory\Application\ProductAvailabilityRepository;
use App\Shared\Domain\Quantity\Quantity;
use Nette\Database\Explorer;
use UnexpectedValueException;

final class DatabaseProductAvailabilityRepository implements ProductAvailabilityRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findActiveVariantAvailability(int $productId): array
    {
        $rows = $this->database->query(
            'SELECT pv.id, pv.sku, si.id AS stock_item_id, si.track_stock, '
            . 'si.allow_backorder, si.quantity_on_hand, si.quantity_reserved '
            . 'FROM product_variant pv '
            . 'LEFT JOIN stock_item si ON si.product_variant_id = pv.id '
            . 'WHERE pv.product_id = ? AND pv.is_active = 1 '
            . 'ORDER BY pv.sort_order ASC, pv.id ASC',
            $productId,
        )->fetchAll();

        $variants = [];
        foreach ($rows as $row) {
            $variantId = $this->integer($row->id, 'product_variant.id');
            $sku = $this->string($row->sku, 'product_variant.sku');

            if ($row->stock_item_id === null) {
                $variants[] = new VariantAvailabilityDto(
                    $variantId,
                    $sku,
                    VariantAvailabilityStatus::MissingStockRecord,
                    null,
                    null,
                    null,
                    false,
                );
                continue;
            }

            $trackStock = $this->boolean($row->track_stock, 'stock_item.track_stock');
            $allowBackorder = $this->boolean($row->allow_backorder, 'stock_item.allow_backorder');

            if (!$trackStock) {
                $variants[] = new VariantAvailabilityDto(
                    $variantId,
                    $sku,
                    VariantAvailabilityStatus::Untracked,
                    null,
                    null,
                    null,
                    $allowBackorder,
                );
                continue;
            }

            $onHand = Quantity::fromDecimal($this->decimal($row->quantity_on_hand, 'stock_item.quantity_on_hand'));
            $reserved = Quantity::fromDecimal($this->decimal($row->quantity_reserved, 'stock_item.quantity_reserved'));
            $physicalAvailable = $onHand->subtract($reserved);
            $available = $physicalAvailable->compareTo(Quantity::fromInteger(0)) > 0
                ? $physicalAvailable
                : Quantity::fromInteger(0);

            $variants[] = new VariantAvailabilityDto(
                $variantId,
                $sku,
                VariantAvailabilityStatus::Tracked,
                $onHand,
                $reserved,
                $available,
                $allowBackorder,
            );
        }

        return $variants;
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A\d+\z/', $value) === 1) {
            return (int) $value;
        }

        throw new UnexpectedValueException(sprintf('Invalid integer value for %s.', $field));
    }

    private function string(mixed $value, string $field): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Invalid string value for %s.', $field));
    }

    private function boolean(mixed $value, string $field): bool
    {
        if (in_array($value, [0, 1, '0', '1'], true)) {
            return (bool) $value;
        }

        throw new UnexpectedValueException(sprintf('Invalid boolean value for %s.', $field));
    }

    private function decimal(mixed $value, string $field): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        throw new UnexpectedValueException(sprintf('Invalid decimal value for %s.', $field));
    }
}
