<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductConfigurationRepository;
use Nette\Database\Explorer;
use UnexpectedValueException;

final readonly class DatabaseProductConfigurationRepository implements ProductConfigurationRepository
{
    public function __construct(private Explorer $database) {}

    public function productExists(int $productId): bool
    {
        return $this->database->table('product')->where('id', $productId)->fetch() !== null;
    }

    public function findAttributeDefinition(int $attributeId): ?array
    {
        $row = $this->database->table('attribute')->get($attributeId);
        if ($row === null) {
            return null;
        }
        if (!is_string($row->type)) {
            throw new UnexpectedValueException('Datový typ atributu v databázi není platný.');
        }
        return ['id' => self::toInt($row->id), 'type' => $row->type];
    }

    public function enumValueBelongsToAttribute(int $attributeId, int $valueId): bool
    {
        return $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('id', $valueId)
            ->fetch() !== null;
    }

    public function hasVariants(int $productId): bool
    {
        return $this->database->table('product_variant')->where('product_id', $productId)->fetch() !== null;
    }

    public function findVariantAxisIds(int $productId): array
    {
        $ids = [];
        foreach ($this->database->table('product_variant_axis')
            ->where('product_id', $productId)
            ->order('sort_order ASC, attribute_id ASC') as $row) {
            $ids[] = self::toInt($row->attribute_id);
        }
        return $ids;
    }

    public function replaceProductAttributes(int $productId, array $attributes): void
    {
        $this->database->table('product_attribute_value')->where('product_id', $productId)->delete();
        foreach ($attributes as $entry) {
            $values = [
                'product_id' => $productId,
                'attribute_id' => $entry['attributeId'],
            ];
            match ($entry['type']) {
                'enum' => $values['attribute_value_id'] = $entry['value'],
                'number' => $values['value_number'] = $entry['value'],
                'text' => $values['value_text'] = $entry['value'],
                'bool' => $values['value_number'] = $entry['value'] === true ? 1 : 0,
                default => throw new UnexpectedValueException('Nepodporovaný typ produktového atributu.'),
            };
            $this->database->table('product_attribute_value')->insert($values);
        }
    }

    public function replaceVariantAxes(int $productId, array $attributeIds): void
    {
        $current = $this->findVariantAxisIds($productId);
        if ($current !== [] && $this->hasVariants($productId)) {
            foreach ($attributeIds as $position => $attributeId) {
                $this->database->table('product_variant_axis')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attributeId)
                    ->update(['sort_order' => $position]);
            }
            return;
        }

        $this->database->table('product_variant_axis')->where('product_id', $productId)->delete();
        foreach ($attributeIds as $position => $attributeId) {
            $this->database->table('product_variant_axis')->insert([
                'product_id' => $productId,
                'attribute_id' => $attributeId,
                'sort_order' => $position,
            ]);
        }
    }

    private static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            if (is_int($parsed)) {
                return $parsed;
            }
        }
        throw new UnexpectedValueException('Databázové ID atributu není platné celé číslo.');
    }
}
