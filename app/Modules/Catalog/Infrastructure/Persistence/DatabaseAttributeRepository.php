<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Api\Dto\AttributeAdminItem;
use App\Modules\Catalog\Application\AttributeAdminRepository;
use App\Modules\Catalog\Application\AttributeWriteRepository;
use App\Modules\Catalog\Domain\Attribute\AttributeType;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final readonly class DatabaseAttributeRepository implements AttributeAdminRepository, AttributeWriteRepository
{
    public function __construct(private Explorer $database) {}

    public function findAll(Locale $locale): array
    {
        $attributes = $this->database->table('attribute')->order('sort_order ASC, id ASC')->fetchAll();
        $names = [];
        foreach ($this->database->table('attribute_translation')->where('locale', $locale->getCode()) as $row) {
            $names[self::toInt($row->attribute_id)] = (string) $row->name;
        }
        $valueCounts = self::countBy($this->database->table('attribute_value')->fetchAll(), 'attribute_id');
        $axisCounts = self::countBy($this->database->table('product_variant_axis')->fetchAll(), 'attribute_id');

        $result = [];
        foreach ($attributes as $row) {
            $id = self::toInt($row->id);
            $unitId = $row->unit_id === null ? null : self::toInt($row->unit_id);
            $result[] = new AttributeAdminItem(
                $id,
                (string) $row->code,
                (string) $row->type,
                $names[$id] ?? null,
                $unitId,
                (bool) $row->is_filterable,
                self::toInt($row->sort_order),
                $valueCounts[$id] ?? 0,
                $axisCounts[$id] ?? 0,
            );
        }
        return $result;
    }

    public function findForEdit(int $attributeId): ?array
    {
        $row = $this->database->table('attribute')->get($attributeId);
        if (!$row instanceof ActiveRow) {
            return null;
        }

        $translations = [];
        foreach ($this->database->table('attribute_translation')->where('attribute_id', $attributeId) as $translation) {
            $translations[(string) $translation->locale] = (string) $translation->name;
        }

        $valueRows = $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();
        $valueIds = [];
        foreach ($valueRows as $valueRow) {
            $valueIds[] = self::toInt($valueRow->id);
        }

        $valueTranslations = [];
        if ($valueIds !== []) {
            foreach ($this->database->table('attribute_value_translation')->where('attribute_value_id', $valueIds) as $translation) {
                $valueId = self::toInt($translation->attribute_value_id);
                $valueTranslations[$valueId][(string) $translation->locale] = (string) $translation->label;
            }
        }

        $values = [];
        foreach ($valueRows as $valueRow) {
            $id = self::toInt($valueRow->id);
            $values[] = [
                'id' => $id,
                'code' => (string) $valueRow->code,
                'sortOrder' => self::toInt($valueRow->sort_order),
                'translations' => $valueTranslations[$id] ?? [],
            ];
        }

        return [
            'id' => self::toInt($row->id),
            'code' => (string) $row->code,
            'type' => (string) $row->type,
            'unitId' => $row->unit_id === null ? null : self::toInt($row->unit_id),
            'isFilterable' => (bool) $row->is_filterable,
            'sortOrder' => self::toInt($row->sort_order),
            'translations' => $translations,
            'values' => $values,
            'isUsed' => $this->hasAttributeAxes($attributeId),
        ];
    }

    public function findUnitOptions(Locale $locale): array
    {
        $names = [];
        foreach ($this->database->table('unit_translation')->where('locale', $locale->getCode()) as $row) {
            $names[self::toInt($row->unit_id)] = (string) $row->name;
        }

        $options = [];
        foreach ($this->database->table('unit')->where('is_active', 1)->order('sort_order ASC, id ASC') as $row) {
            $id = self::toInt($row->id);
            $options[$id] = $names[$id] ?? (string) $row->code;
        }
        return $options;
    }

    public function isCodeAvailable(string $code, ?int $excludeAttributeId = null): bool
    {
        $selection = $this->database->table('attribute')->where('code', $code);
        if ($excludeAttributeId !== null) {
            $selection->where('id != ?', $excludeAttributeId);
        }
        return $selection->fetch() === null;
    }

    public function unitExists(int $unitId): bool
    {
        return $this->database->table('unit')->where('id', $unitId)->where('is_active', 1)->fetch() !== null;
    }

    public function attributeExists(int $attributeId): bool
    {
        return $this->database->table('attribute')->where('id', $attributeId)->fetch() !== null;
    }

    public function findAttributeType(int $attributeId): ?AttributeType
    {
        $row = $this->database->table('attribute')->get($attributeId);
        if (!$row instanceof ActiveRow) {
            return null;
        }
        return AttributeType::from((string) $row->type);
    }

    public function hasAttributeAxes(int $attributeId): bool
    {
        return $this->database->table('product_variant_axis')->where('attribute_id', $attributeId)->fetch() !== null;
    }

    public function hasAttributeValues(int $attributeId): bool
    {
        return $this->database->table('attribute_value')->where('attribute_id', $attributeId)->fetch() !== null;
    }

    public function createAttribute(string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder): int
    {
        return self::insertedId($this->database->table('attribute')->insert([
            'code' => $code,
            'type' => $type->value,
            'unit_id' => $unitId,
            'is_filterable' => $isFilterable,
            'sort_order' => $sortOrder,
        ]));
    }

    public function updateAttribute(int $attributeId, string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder): void
    {
        $this->database->table('attribute')->where('id', $attributeId)->update([
            'code' => $code,
            'type' => $type->value,
            'unit_id' => $unitId,
            'is_filterable' => $isFilterable,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deleteAttribute(int $attributeId): void
    {
        $this->database->table('attribute')->where('id', $attributeId)->delete();
    }

    public function isValueCodeAvailable(int $attributeId, string $code, ?int $excludeValueId = null): bool
    {
        $selection = $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('code', $code);
        if ($excludeValueId !== null) {
            $selection->where('id != ?', $excludeValueId);
        }
        return $selection->fetch() === null;
    }

    public function valueExists(int $attributeId, int $valueId): bool
    {
        return $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('id', $valueId)
            ->fetch() !== null;
    }

    public function isValueUsed(int $attributeId, int $valueId): bool
    {
        return $this->database->table('product_variant_attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('attribute_value_id', $valueId)
            ->fetch() !== null;
    }

    public function createValue(int $attributeId, string $code, int $sortOrder): int
    {
        return self::insertedId($this->database->table('attribute_value')->insert([
            'attribute_id' => $attributeId,
            'code' => $code,
            'sort_order' => $sortOrder,
        ]));
    }

    public function updateValue(int $attributeId, int $valueId, string $code, int $sortOrder): void
    {
        $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('id', $valueId)
            ->update(['code' => $code, 'sort_order' => $sortOrder]);
    }

    public function deleteValue(int $attributeId, int $valueId): void
    {
        $this->database->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->where('id', $valueId)
            ->delete();
    }

    public function saveAttributeTranslation(int $attributeId, string $locale, string $name): void
    {
        $selection = $this->database->table('attribute_translation')
            ->where('attribute_id', $attributeId)
            ->where('locale', $locale);
        if ($selection->fetch() === null) {
            $this->database->table('attribute_translation')->insert([
                'attribute_id' => $attributeId,
                'locale' => $locale,
                'name' => $name,
            ]);
            return;
        }
        $selection->update(['name' => $name]);
    }

    public function saveValueTranslation(int $valueId, string $locale, string $label): void
    {
        $selection = $this->database->table('attribute_value_translation')
            ->where('attribute_value_id', $valueId)
            ->where('locale', $locale);
        if ($selection->fetch() === null) {
            $this->database->table('attribute_value_translation')->insert([
                'attribute_value_id' => $valueId,
                'locale' => $locale,
                'label' => $label,
            ]);
            return;
        }
        $selection->update(['label' => $label]);
    }

    /** @param list<ActiveRow> $rows
     *  @return array<int, int>
     */
    private static function countBy(array $rows, string $field): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $id = self::toInt($row->$field);
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }
        return $counts;
    }

    private static function insertedId(mixed $row): int
    {
        if (!$row instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nevrátila vložený řádek atributu.');
        }
        return self::toInt($row->id);
    }

    private static function toInt(mixed $value): int
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
        throw new UnexpectedValueException('Databáze vrátila neplatnou celočíselnou hodnotu atributu.');
    }
}
