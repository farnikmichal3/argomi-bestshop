<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductCustomerOptionRepository;
use Nette\Database\Explorer;
use UnexpectedValueException;

final readonly class DatabaseProductCustomerOptionRepository implements ProductCustomerOptionRepository
{
    public function __construct(private Explorer $database) {}

    public function productExists(int $productId): bool
    {
        return $this->database->table('product')->where('id', $productId)->fetch() !== null;
    }

    public function findForProduct(int $productId): array
    {
        $optionRows = $this->database->table('product_option')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        if ($optionRows === []) {
            return [];
        }

        $optionIds = [];
        foreach ($optionRows as $row) {
            $optionIds[] = self::toInt($row->id, 'product_option.id');
        }

        $optionTranslations = [];
        foreach ($this->database->table('product_option_translation')->where('product_option_id', $optionIds) as $row) {
            $optionTranslations[self::toInt($row->product_option_id, 'product_option_translation.product_option_id')]
                [self::toString($row->locale, 'product_option_translation.locale')]
                = self::toString($row->name, 'product_option_translation.name');
        }

        $valueRows = $this->database->table('product_option_value')
            ->where('product_option_id', $optionIds)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        $valueIds = [];
        foreach ($valueRows as $row) {
            $valueIds[] = self::toInt($row->id, 'product_option_value.id');
        }

        $valueTranslations = [];
        if ($valueIds !== []) {
            foreach ($this->database->table('product_option_value_translation')->where('product_option_value_id', $valueIds) as $row) {
                $valueTranslations[self::toInt($row->product_option_value_id, 'product_option_value_translation.product_option_value_id')]
                    [self::toString($row->locale, 'product_option_value_translation.locale')]
                    = self::toString($row->label, 'product_option_value_translation.label');
            }
        }

        $valuesByOption = [];
        foreach ($valueRows as $row) {
            $optionId = self::toInt($row->product_option_id, 'product_option_value.product_option_id');
            $valueId = self::toInt($row->id, 'product_option_value.id');
            $valuesByOption[$optionId][] = [
                'id' => $valueId,
                'code' => self::toString($row->code, 'product_option_value.code'),
                'sortOrder' => self::toInt($row->sort_order, 'product_option_value.sort_order'),
                'translations' => $valueTranslations[$valueId] ?? [],
            ];
        }

        $result = [];
        foreach ($optionRows as $row) {
            $id = self::toInt($row->id, 'product_option.id');
            $result[] = [
                'id' => $id,
                'code' => self::toString($row->code, 'product_option.code'),
                'type' => self::toString($row->type, 'product_option.type'),
                'isRequired' => self::toBool($row->is_required, 'product_option.is_required'),
                'sortOrder' => self::toInt($row->sort_order, 'product_option.sort_order'),
                'translations' => $optionTranslations[$id] ?? [],
                'values' => $valuesByOption[$id] ?? [],
            ];
        }

        return $result;
    }

    public function optionUsedInCart(int $optionId): bool
    {
        return $this->database->table('cart_item_option')->where('product_option_id', $optionId)->fetch() !== null;
    }

    public function valueUsedInCart(int $valueId): bool
    {
        return $this->database->table('cart_item_option')->where('product_option_value_id', $valueId)->fetch() !== null;
    }

    public function createOption(int $productId, string $code, string $type, bool $isRequired, int $sortOrder): int
    {
        return self::insertedId($this->database->table('product_option')->insert([
            'product_id' => $productId,
            'code' => $code,
            'type' => $type,
            'is_required' => $isRequired,
            'sort_order' => $sortOrder,
        ]));
    }

    public function updateOption(int $productId, int $optionId, string $code, string $type, bool $isRequired, int $sortOrder): void
    {
        $this->database->table('product_option')
            ->where('id', $optionId)
            ->where('product_id', $productId)
            ->update([
                'code' => $code,
                'type' => $type,
                'is_required' => $isRequired,
                'sort_order' => $sortOrder,
            ]);
    }

    public function deleteOption(int $productId, int $optionId): void
    {
        $this->database->table('product_option')->where('id', $optionId)->where('product_id', $productId)->delete();
    }

    public function createValue(int $optionId, string $code, int $sortOrder): int
    {
        return self::insertedId($this->database->table('product_option_value')->insert([
            'product_option_id' => $optionId,
            'code' => $code,
            'sort_order' => $sortOrder,
        ]));
    }

    public function updateValue(int $optionId, int $valueId, string $code, int $sortOrder): void
    {
        $this->database->table('product_option_value')
            ->where('id', $valueId)
            ->where('product_option_id', $optionId)
            ->update(['code' => $code, 'sort_order' => $sortOrder]);
    }

    public function deleteValue(int $optionId, int $valueId): void
    {
        $this->database->table('product_option_value')
            ->where('id', $valueId)
            ->where('product_option_id', $optionId)
            ->delete();
    }

    public function saveOptionTranslation(int $optionId, string $locale, string $name): void
    {
        $selection = $this->database->table('product_option_translation')
            ->where('product_option_id', $optionId)
            ->where('locale', $locale);
        if ($selection->fetch() === null) {
            $this->database->table('product_option_translation')->insert([
                'product_option_id' => $optionId,
                'locale' => $locale,
                'name' => $name,
            ]);
            return;
        }
        $selection->update(['name' => $name]);
    }

    public function saveValueTranslation(int $valueId, string $locale, string $label): void
    {
        $selection = $this->database->table('product_option_value_translation')
            ->where('product_option_value_id', $valueId)
            ->where('locale', $locale);
        if ($selection->fetch() === null) {
            $this->database->table('product_option_value_translation')->insert([
                'product_option_value_id' => $valueId,
                'locale' => $locale,
                'label' => $label,
            ]);
            return;
        }
        $selection->update(['label' => $label]);
    }

    private static function insertedId(mixed $row): int
    {
        $id = is_object($row) && isset($row->id) ? $row->id : null;
        return self::toInt($id, 'inserted id');
    }

    private static function toInt(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            if ($parsed !== false) {
                return $parsed;
            }
        }
        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné celé číslo.', $field));
    }

    private static function toString(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }
        return $value;
    }

    private static function toBool(mixed $value, string $field): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }
        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platný boolean.', $field));
    }
}
