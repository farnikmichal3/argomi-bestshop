<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\VariantSelectionRepository;
use Nette\Database\Explorer;
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

        $axisRows = $this->database->table('product_variant_axis')
            ->where('product_id', $productId)
            ->order('attribute_id ASC')
            ->fetchAll();

        if ($axisRows === []) {
            return [];
        }

        $attributeIds = [];
        foreach ($axisRows as $row) {
            $attributeIds[] = $row->attribute_id;
        }

        $axes = [];
        foreach (
            $this->database->table('attribute')
                ->where('id', $attributeIds)
                ->fetchAll() as $attribute
        ) {
            if (!is_string($attribute->code) || !is_string($attribute->type)) {
                throw new UnexpectedValueException('Definice variantní osy obsahuje neplatný kód nebo typ.');
            }

            $axes[$attribute->code] = $attribute->type;
        }

        if (count($axes) !== count($axisRows)) {
            throw new UnexpectedValueException('Varianta produktu odkazuje na neexistující definici osy.');
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
