<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductEditorConfigurationRepository;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final readonly class DatabaseProductEditorConfigurationRepository implements ProductEditorConfigurationRepository
{
    public function __construct(private Explorer $database) {}

    public function productExists(int $productId): bool
    {
        return $this->database->table('product')->where('id', $productId)->fetch() !== null;
    }

    public function findAvailableAttributes(Locale $locale): array
    {
        $unitTranslations = [];
        foreach ($this->database->table('unit_translation')->where('locale', $locale->getCode()) as $translation) {
            $unitId = self::toInt($translation->unit_id);
            $unitTranslations[$unitId] = [
                'name' => self::toString($translation->name),
                'symbol' => self::toString($translation->symbol),
            ];
        }

        $attributeTranslations = [];
        foreach ($this->database->table('attribute_translation')->where('locale', $locale->getCode()) as $translation) {
            $attributeTranslations[self::toInt($translation->attribute_id)] = self::toString($translation->name);
        }

        $valueTranslations = [];
        foreach ($this->database->table('attribute_value_translation')->where('locale', $locale->getCode()) as $translation) {
            $valueTranslations[self::toInt($translation->attribute_value_id)] = self::toString($translation->label);
        }

        $valueRows = $this->database->table('attribute_value')
            ->order('attribute_id ASC, sort_order ASC, id ASC')
            ->fetchAll();
        $valuesByAttribute = [];
        foreach ($valueRows as $valueRow) {
            $id = self::toInt($valueRow->id);
            $attributeId = self::toInt($valueRow->attribute_id);
            $code = self::toString($valueRow->code);
            $valuesByAttribute[$attributeId][] = [
                'id' => $id,
                'code' => $code,
                'label' => $valueTranslations[$id] ?? null,
            ];
        }

        $result = [];
        foreach ($this->database->table('attribute')->order('sort_order ASC, id ASC') as $row) {
            $id = self::toInt($row->id);
            $unitId = $row->unit_id === null ? null : self::toInt($row->unit_id);
            $unit = $unitId === null ? null : ($unitTranslations[$unitId] ?? null);
            $result[] = [
                'id' => $id,
                'code' => self::toString($row->code),
                'type' => self::toString($row->type),
                'name' => $attributeTranslations[$id] ?? null,
                'unitName' => $unit['name'] ?? null,
                'unitSymbol' => $unit['symbol'] ?? null,
                'values' => $valuesByAttribute[$id] ?? [],
            ];
        }

        return $result;
    }

    public function findAssignedValues(int $productId): array
    {
        $result = [];
        foreach ($this->database->table('product_attribute_value')
            ->select('*, CAST(value_number AS CHAR) AS value_number_string')
            ->where('product_id', $productId) as $row) {
            $attributeId = self::toInt($row->attribute_id);
            $attribute = $this->database->table('attribute')->get($attributeId);
            if (!$attribute instanceof ActiveRow) {
                throw new UnexpectedValueException('Produkt odkazuje na neexistující atribut.');
            }

            $type = self::toString($attribute->type);
            $value = match ($type) {
                'enum' => self::nullableInt($row->attribute_value_id),
                'number' => self::nullableDecimal($row->value_number_string),
                'text' => self::nullableString($row->value_text),
                'bool' => self::nullableBool($row->value_number_string),
                default => throw new UnexpectedValueException('Datový typ produktového atributu není podporován.'),
            };
            if ($value !== null) {
                $result[$attributeId] = $value;
            }
        }

        return $result;
    }

    public function findVariantAxisIds(int $productId): array
    {
        $result = [];
        foreach ($this->database->table('product_variant_axis')
            ->where('product_id', $productId)
            ->order('sort_order ASC, attribute_id ASC') as $row) {
            $result[] = self::toInt($row->attribute_id);
        }
        return $result;
    }

    private static function toString(mixed $value): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Databáze vrátila neplatnou textovou hodnotu konfigurace produktu.');
        }
        return $value;
    }

    private static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value) && is_int(filter_var($value, FILTER_VALIDATE_INT))) {
            return (int) $value;
        }
        throw new UnexpectedValueException('Databáze vrátila neplatné ID konfigurace produktu.');
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : self::toInt($value);
    }

    private static function nullableDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }
        throw new UnexpectedValueException('Databáze vrátila neplatnou číselnou hodnotu atributu.');
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::toString($value);
    }

    private static function nullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }
        if ($value === 0 || $value === '0') {
            return false;
        }
        if ($value === 1 || $value === '1') {
            return true;
        }
        throw new UnexpectedValueException('Databáze vrátila neplatnou logickou hodnotu atributu.');
    }
}
