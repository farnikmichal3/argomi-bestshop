<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Infrastructure\\Persistence;

use App\\Modules\\Catalog\\Application\\VariantSelectionRepository;
use Nette\\Database\\Explorer;
use UnexpectedValueException;

/**
 * Databázové čtení os produktu a odpovídající aktivní varianty.
 */
final readonly class DatabaseVariantSelectionRepository implements VariantSelectionRepository
{
    public function __construct(
        private Explorer $database,
    ) {}

    public function findActiveAxisTypes(int $productId): ?array
    {
        $product = $this->database->table('product')
            ->where('id', $productId)
            ->where('status', 'active')
            ->fetch();

        if ($product === null) {
            return null;
        }

        $axes = [];
        foreach (
            $this->database->table('product_variant_axis')
                ->where('product_id', $productId)
                ->order('attribute_id ASC')
                ->fetchAll() as $row
        ) {
            $attribute = $this->database->table('attribute')
                ->where('id', $row->attribute_id)
                ->fetch();

            if ($attribute === null || !is_string($attribute->code) || !is_string($attribute->type)) {
                throw new UnexpectedValueException('Varianta produktu odkazuje na neplatnou definici osy.');
            }

            $axes[$attribute->code] = $attribute->type;
        }

        return $axes;
    }

    public function findActiveVariantId(int $productId, string $combinationHash): ?int
    {
        $variant = $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('combination_hash', $combinationHash)
            ->where('is_active', 1)
            ->fetch();

        if ($variant === null) {
            return null;
        }

        $id = $variant->id;
        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            $validated = filter_var($id, FILTER_VALIDATE_INT);
            if ($validated !== false) {
                return $validated;
            }
        }

        throw new UnexpectedValueException('ID nalezené varianty není platné celé číslo.');
    }
}
