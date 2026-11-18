<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductImageDto;
use App\Modules\Catalog\Application\ProductImageRepository;
use Nette\Database\Explorer;
use UnexpectedValueException;

final class DatabaseProductImageRepository implements ProductImageRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findForProduct(int $productId, array $activeVariantIds): array
    {
        $activeVariantIdSet = array_fill_keys($activeVariantIds, true);
        $images = [];

        foreach (
            $this->database->table('product_image')
                ->where('product_id', $productId)
                ->order('sort_order ASC, id ASC')
                ->fetchAll() as $row
        ) {
            $variantId = $row->product_variant_id;
            if ($variantId !== null) {
                $variantId = $this->integer($variantId, 'product_image.product_variant_id');
                if (!isset($activeVariantIdSet[$variantId])) {
                    continue;
                }
            }

            $images[] = new ProductImageDto(
                $this->integer($row->id, 'product_image.id'),
                $variantId,
                $this->string($row->file_path, 'product_image.file_path'),
                $this->nullableString($row->alt_text, 'product_image.alt_text'),
                $this->integer($row->sort_order, 'product_image.sort_order'),
            );
        }

        return $images;
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A-?[0-9]+\z/D', $value) === 1) {
            $validated = filter_var($value, FILTER_VALIDATE_INT);
            if ($validated !== false) {
                return $validated;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné celé číslo.', $field));
    }

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }

        return $value;
    }

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }
}
