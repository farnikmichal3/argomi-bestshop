<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductVariantGenerationRepository;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final readonly class DatabaseProductVariantGenerationRepository implements ProductVariantGenerationRepository
{
    public function __construct(private Explorer $database) {}

    public function findProductStatus(int $productId): ?string
    {
        $status = $this->database->table('product')->where('id', $productId)->fetch()?->status;
        if ($status === null || is_string($status)) {
            return $status;
        }
        throw new UnexpectedValueException('Stav produktu není platný text.');
    }

    public function findAxisDefinitions(int $productId): array
    {
        $axisRows = $this->database->table('product_variant_axis')
            ->where('product_id', $productId)
            ->order('sort_order ASC, attribute_id ASC')
            ->fetchAll();
        if ($axisRows === []) {
            return [];
        }

        $ids = [];
        foreach ($axisRows as $row) {
            $ids[] = $this->toInt($row->attribute_id, 'product_variant_axis.attribute_id');
        }

        $axes = [];
        foreach ($this->database->table('attribute')->where('id', $ids)->fetchAll() as $row) {
            if (!is_string($row->code) || !is_string($row->type)) {
                throw new UnexpectedValueException('Definice variantní osy je neplatná.');
            }
            $axes[$row->code] = [
                'attributeId' => $this->toInt($row->id, 'attribute.id'),
                'type' => $row->type,
            ];
        }
        if (count($axes) !== count($axisRows)) {
            throw new UnexpectedValueException('Produktová osa odkazuje na neexistující atribut.');
        }
        return $axes;
    }

    public function enumValueExists(int $attributeId, string $valueCode): bool
    {
        return $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('code', $valueCode)
            ->fetch() !== null;
    }

    public function variantExists(int $productId, string $combinationHash): bool
    {
        return $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('combination_hash', $combinationHash)
            ->fetch() !== null;
    }

    public function nextSortOrder(int $productId): int
    {
        $row = $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->order('sort_order DESC, id DESC')
            ->fetch();
        return $row === null ? 0 : $this->toInt($row->sort_order, 'product_variant.sort_order') + 1;
    }

    public function createVariant(int $productId, string $sku, string $combinationHash, int $sortOrder): int
    {
        $row = $this->database->table('product_variant')->insert([
            'product_id' => $productId,
            'sku' => $sku,
            'combination_hash' => $combinationHash,
            'is_default' => 0,
            'is_active' => 1,
            'sort_order' => $sortOrder,
        ]);
        if (!$row instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nepotvrdila vytvoření varianty.');
        }
        return $this->toInt($row->id, 'product_variant.id');
    }

    public function insertVariantAttributeValue(
        int $productId,
        string $attributeCode,
        int $attributeId,
        int $variantId,
        string $type,
        string|bool $value,
    ): void {
        match ($type) {
            'enum' => $this->insertEnumValue($productId, $attributeId, $variantId, $value),
            'number' => $this->insertNumberValue($productId, $attributeId, $variantId, $value),
            'text' => $this->insertTextValue($productId, $attributeId, $variantId, $value),
            'bool' => $this->insertBooleanValue($productId, $attributeId, $variantId, $value),
            default => throw new UnexpectedValueException(sprintf('Atribut %s má nepodporovaný typ.', $attributeCode)),
        };
    }

    private function insertEnumValue(int $productId, int $attributeId, int $variantId, string|bool $value): ActiveRow
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Enum hodnota musí být textový kód.');
        }
        $enum = $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('code', $value)
            ->fetch();
        if ($enum === null) {
            throw new UnexpectedValueException('Enum hodnota nebyla nalezena.');
        }
        return $this->insertAttributeValue([
            'product_variant_id' => $variantId,
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'attribute_value_id' => $this->toInt($enum->id, 'attribute_value.id'),
        ]);
    }

    private function insertNumberValue(int $productId, int $attributeId, int $variantId, string|bool $value): ActiveRow
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Číselná hodnota musí být desetinný řetězec.');
        }
        return $this->insertAttributeValue([
            'product_variant_id' => $variantId,
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'value_number' => $value,
        ]);
    }

    private function insertTextValue(int $productId, int $attributeId, int $variantId, string|bool $value): ActiveRow
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Textová hodnota musí být řetězec.');
        }
        return $this->database->table('product_variant_attribute_value')->insert([
            'product_variant_id' => $variantId,
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'value_text' => $value,
        ]);
    }

    private function insertBooleanValue(int $productId, int $attributeId, int $variantId, string|bool $value): ActiveRow
    {
        if (!is_bool($value)) {
            throw new UnexpectedValueException('Logická hodnota musí být boolean.');
        }
        return $this->database->table('product_variant_attribute_value')->insert([
            'product_variant_id' => $variantId,
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'value_number' => $value ? 1 : 0,
        ]);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function insertAttributeValue(array $values): ActiveRow
    {
        $row = $this->database->table('product_variant_attribute_value')->insert($values);
        if (!$row instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nepotvrdila uložení hodnoty varianty.');
        }
        return $row;
    }

    private function toInt(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if (is_int($integer)) {
                return $integer;
            }
        }
        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné celé číslo.', $field));
    }
}
