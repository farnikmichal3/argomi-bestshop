<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Api\Dto\ProductVariantMatrix;
use App\Modules\Catalog\Api\Dto\VariantMatrixAxis;
use App\Modules\Catalog\Api\Dto\VariantMatrixCombination;
use App\Modules\Catalog\Api\Dto\VariantMatrixValue;
use App\Modules\Catalog\Api\ProductVariantMatrixReader;
use App\Modules\Catalog\Api\VariantMatrixTooLargeException;
use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
use App\Shared\Domain\Locale\Locale;
use InvalidArgumentException;
use Nette\Database\Explorer;
use UnexpectedValueException;

/** Dávkový administrační read model os, hodnot a existujících variant produktu. */
final readonly class DatabaseProductVariantMatrixRepository implements ProductVariantMatrixReader
{
    private const MAX_COMBINATIONS = 256;

    public function __construct(private Explorer $database) {}

    public function findForProduct(int $productId, Locale $locale): ?ProductVariantMatrix
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }

        $product = $this->database->table('product')->where('id', $productId)->fetch();
        if ($product === null) {
            return null;
        }

        $productName = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $locale->getCode())
            ->fetch()?->name;
        if ($productName !== null && !is_string($productName)) {
            throw new UnexpectedValueException('Překlad názvu produktu není platný text.');
        }

        $axisRows = $this->database->table('product_variant_axis')
            ->where('product_id', $productId)
            ->order('sort_order ASC, attribute_id ASC')
            ->fetchAll();

        if ($axisRows === []) {
            return new ProductVariantMatrix(
                $productId,
                $this->string($product->code, 'product.code'),
                $productName,
                [],
                [],
            );
        }

        $attributeIds = [];
        foreach ($axisRows as $row) {
            $attributeIds[] = $this->integer($row->attribute_id, 'product_variant_axis.attribute_id');
        }

        $attributes = [];
        foreach ($this->database->table('attribute')->where('id', $attributeIds)->fetchAll() as $row) {
            $id = $this->integer($row->id, 'attribute.id');
            $attributes[$id] = [
                'code' => $this->string($row->code, 'attribute.code'),
                'type' => $this->string($row->type, 'attribute.type'),
            ];
        }
        if (count($attributes) !== count($axisRows)) {
            throw new UnexpectedValueException('Produktová osa odkazuje na neexistující atribut.');
        }

        $attributeNames = [];
        foreach ($this->database->table('attribute_translation')
            ->where('attribute_id', $attributeIds)
            ->where('locale', $locale->getCode())
            ->fetchAll() as $row) {
            $attributeNames[$this->integer($row->attribute_id, 'attribute_translation.attribute_id')]
                = $this->string($row->name, 'attribute_translation.name');
        }

        $enumAttributeIds = [];
        foreach ($attributes as $id => $attribute) {
            if ($attribute['type'] === 'enum') {
                $enumAttributeIds[] = $id;
            }
        }

        $enumRows = $enumAttributeIds === [] ? [] : $this->database->table('attribute_value')
            ->where('attribute_id', $enumAttributeIds)
            ->order('attribute_id ASC, sort_order ASC, id ASC')
            ->fetchAll();

        $enumLabels = [];
        $enumIds = [];
        foreach ($enumRows as $row) {
            $enumIds[] = $this->integer($row->id, 'attribute_value.id');
        }
        if ($enumIds !== []) {
            foreach ($this->database->table('attribute_value_translation')
                ->where('attribute_value_id', $enumIds)
                ->where('locale', $locale->getCode())
                ->fetchAll() as $row) {
                $enumLabels[$this->integer($row->attribute_value_id, 'attribute_value_translation.attribute_value_id')]
                    = $this->string($row->label, 'attribute_value_translation.label');
            }
        }

        $enumValuesByAttribute = [];
        $enumValuesById = [];
        foreach ($enumRows as $row) {
            $id = $this->integer($row->id, 'attribute_value.id');
            $attributeId = $this->integer($row->attribute_id, 'attribute_value.attribute_id');
            $value = new VariantMatrixValue(
                $this->string($row->code, 'attribute_value.code'),
                $enumLabels[$id] ?? null,
                $id,
            );
            $enumValuesByAttribute[$attributeId][] = $value;
            $enumValuesById[$id] = $value;
        }

        $variantRows = $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        $variantIds = [];
        foreach ($variantRows as $row) {
            $variantIds[] = $this->integer($row->id, 'product_variant.id');
        }
        $variantValueRows = $variantIds === [] ? [] : $this->database->table('product_variant_attribute_value')
            ->select(
                'id, product_variant_id, product_id, attribute_id, attribute_value_id, '
                . 'CAST(value_number AS CHAR) AS value_number_text, value_text',
            )
            ->where('product_variant_id', $variantIds)
            ->fetchAll();

        $axisById = [];
        foreach ($axisRows as $row) {
            $id = $this->integer($row->attribute_id, 'product_variant_axis.attribute_id');
            $axisById[$id] = $attributes[$id];
        }

        $variantValuesById = [];
        $derivedValuesByAttribute = [];
        foreach ($variantValueRows as $row) {
            $variantId = $this->integer($row->product_variant_id, 'product_variant_attribute_value.product_variant_id');
            $attributeId = $this->integer($row->attribute_id, 'product_variant_attribute_value.attribute_id');
            $attribute = $axisById[$attributeId] ?? null;
            if ($attribute === null) {
                throw new UnexpectedValueException('Varianta obsahuje hodnotu mimo osy svého produktu.');
            }

            $type = $attribute['type'];
            $valueId = $this->nullableInteger(
                $row->attribute_value_id,
                'product_variant_attribute_value.attribute_value_id',
            );
            $value = match ($type) {
                'enum' => $this->enumValue($valueId, $row->value_number_text, $row->value_text, $enumValuesById),
                'number' => $this->numberValue($valueId, $row->value_number_text, $row->value_text, $attribute['code']),
                'text' => $this->textValue($valueId, $row->value_number_text, $row->value_text),
                'bool' => $this->booleanValue($valueId, $row->value_number_text, $row->value_text),
                default => throw new UnexpectedValueException('Atribut má nepodporovaný datový typ.'),
            };

            $variantValuesById[$variantId][$attribute['code']] = [
                'type' => $type,
                'value' => $value->value,
            ];

            if ($type === 'number' || $type === 'text') {
                $key = (string) $value->value;
                $derivedValuesByAttribute[$attributeId][$key] = $value;
            }
        }

        $axes = [];
        foreach ($axisRows as $row) {
            $attributeId = $this->integer($row->attribute_id, 'product_variant_axis.attribute_id');
            $attribute = $attributes[$attributeId];
            $type = $attribute['type'];
            $values = match ($type) {
                'enum' => $enumValuesByAttribute[$attributeId] ?? [],
                'number', 'text' => array_values($derivedValuesByAttribute[$attributeId] ?? []),
                'bool' => [new VariantMatrixValue(false, null), new VariantMatrixValue(true, null)],
                default => throw new UnexpectedValueException('Osa má nepodporovaný datový typ.'),
            };
            if ($type === 'number') {
                usort(
                    $values,
                    fn(VariantMatrixValue $a, VariantMatrixValue $b): int => $this->compareNumbers(
                        (string) $a->value,
                        (string) $b->value,
                    ),
                );
            } elseif ($type === 'text') {
                usort(
                    $values,
                    static fn(VariantMatrixValue $a, VariantMatrixValue $b): int => strcmp(
                        (string) $a->value,
                        (string) $b->value,
                    ),
                );
            }
            $axes[] = new VariantMatrixAxis(
                $attributeId,
                $attribute['code'],
                $attributeNames[$attributeId] ?? null,
                $type,
                $values,
            );
        }

        $existingByHash = [];
        foreach ($variantRows as $row) {
            $id = $this->integer($row->id, 'product_variant.id');
            $storedValues = $variantValuesById[$id] ?? [];
            if (count($storedValues) !== count($axes)) {
                throw new UnexpectedValueException(
                    'Existující varianta nemá právě jednu hodnotu pro každou osu produktu.',
                );
            }
            $hashInput = [];
            foreach ($axes as $axis) {
                $value = $storedValues[$axis->code] ?? null;
                if (!is_array($value)) {
                    throw new UnexpectedValueException('Existující varianta neobsahuje hodnotu jedné z os.');
                }
                $hashInput[$axis->code] = ['type' => $axis->type, 'value' => $value['value']];
            }
            $hash = VariantCombinationHash::create($hashInput);
            $storedHash = $this->string($row->combination_hash, 'product_variant.combination_hash');
            if (!hash_equals($storedHash, $hash)) {
                throw new UnexpectedValueException('Uložený hash varianty neodpovídá jejím hodnotám os.');
            }
            $existingByHash[$hash] = [
                'id' => $id,
                'sku' => $this->string($row->sku, 'product_variant.sku'),
                'isActive' => $this->boolean($row->is_active, 'product_variant.is_active'),
                'isDefault' => $this->boolean($row->is_default, 'product_variant.is_default'),
            ];
        }

        $combinations = [];
        $hasEmptyAxis = false;
        foreach ($axes as $axis) {
            if ($axis->values === []) {
                $hasEmptyAxis = true;
                break;
            }
        }
        if (!$hasEmptyAxis) {
            $this->appendCombinations($axes, 0, [], $existingByHash, $combinations);
        }

        return new ProductVariantMatrix(
            $productId,
            $this->string($product->code, 'product.code'),
            $productName,
            $axes,
            $combinations,
        );
    }

    /**
     * @param list<VariantMatrixAxis> $axes
     * @param array<string, array{type: string, value: string|bool}> $values
     * @param array<string, array{id: int, sku: string, isActive: bool, isDefault: bool}> $existingByHash
     * @param list<VariantMatrixCombination> $combinations
     */
    private function appendCombinations(
        array $axes,
        int $index,
        array $values,
        array $existingByHash,
        array &$combinations,
    ): void {
        if (count($combinations) >= self::MAX_COMBINATIONS) {
            throw new VariantMatrixTooLargeException('Matice variant překračuje bezpečný limit 256 kombinací.');
        }
        if ($index === count($axes)) {
            $hash = VariantCombinationHash::create($values);
            $existing = $existingByHash[$hash] ?? null;
            $labels = [];
            foreach ($axes as $axis) {
                $selected = $values[$axis->code]['value'];
                $optionLabel = null;
                foreach ($axis->values as $option) {
                    if ($option->value === $selected) {
                        $optionLabel = $option->label;
                        break;
                    }
                }
                $displayValue = $optionLabel ?? (is_bool($selected) ? ($selected ? 'true' : 'false') : $selected);
                $labels[] = ($axis->name ?? $axis->code) . ': ' . $displayValue;
            }
            $combinations[] = new VariantMatrixCombination(
                $hash,
                $values,
                implode(' · ', $labels),
                $existing['id'] ?? null,
                $existing['sku'] ?? null,
                $existing['isActive'] ?? null,
                $existing['isDefault'] ?? null,
            );
            return;
        }

        $axis = $axes[$index];
        foreach ($axis->values as $option) {
            $next = $values;
            $next[$axis->code] = ['type' => $axis->type, 'value' => $option->value];
            $this->appendCombinations($axes, $index + 1, $next, $existingByHash, $combinations);
        }
    }

    private function compareNumbers(string $left, string $right): int
    {
        $leftNegative = str_starts_with($left, '-');
        $rightNegative = str_starts_with($right, '-');
        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $leftUnsigned = $leftNegative ? substr($left, 1) : $left;
        $rightUnsigned = $rightNegative ? substr($right, 1) : $right;
        [$leftInteger, $leftFraction] = array_pad(explode('.', $leftUnsigned, 2), 2, '');
        [$rightInteger, $rightFraction] = array_pad(explode('.', $rightUnsigned, 2), 2, '');

        $leftInteger = ltrim($leftInteger, '0');
        $rightInteger = ltrim($rightInteger, '0');
        if ($leftInteger === '') {
            $leftInteger = '0';
        }
        if ($rightInteger === '') {
            $rightInteger = '0';
        }
        $comparison = strlen($leftInteger) <=> strlen($rightInteger);
        if ($comparison === 0) {
            $comparison = strcmp($leftInteger, $rightInteger);
        }

        if ($comparison === 0) {
            $fractionLength = max(strlen($leftFraction), strlen($rightFraction));
            $comparison = strcmp(
                str_pad($leftFraction, $fractionLength, '0'),
                str_pad($rightFraction, $fractionLength, '0'),
            );
        }

        return $leftNegative ? -$comparison : $comparison;
    }

    /** @param array<int, VariantMatrixValue> $enumValuesById */
    private function enumValue(?int $valueId, mixed $number, mixed $text, array $enumValuesById): VariantMatrixValue
    {
        if ($valueId === null || $number !== null || $text !== null || !isset($enumValuesById[$valueId])) {
            throw new UnexpectedValueException('Enum hodnota varianty je neplatná.');
        }
        return $enumValuesById[$valueId];
    }

    private function numberValue(?int $valueId, mixed $number, mixed $text, string $code): VariantMatrixValue
    {
        if ($valueId !== null || $number === null || $text !== null) {
            throw new UnexpectedValueException('Číselná hodnota varianty je neplatná.');
        }
        $raw = $this->string($number, 'product_variant_attribute_value.value_number_text');
        $canonical = VariantCombinationHash::canonicalize([$code => ['type' => 'number', 'value' => $raw]]);
        $normalized = substr($canonical, strlen($code) + 1);
        return new VariantMatrixValue($normalized, $normalized);
    }

    private function textValue(?int $valueId, mixed $number, mixed $text): VariantMatrixValue
    {
        if ($valueId !== null || $number !== null || !is_string($text) || $text === '') {
            throw new UnexpectedValueException('Textová hodnota varianty je neplatná.');
        }
        return new VariantMatrixValue($text, $text);
    }

    private function booleanValue(?int $valueId, mixed $number, mixed $text): VariantMatrixValue
    {
        if ($valueId !== null || $number === null || $text !== null) {
            throw new UnexpectedValueException('Logická hodnota varianty je neplatná.');
        }
        $raw = $this->string($number, 'product_variant_attribute_value.value_number_text');
        $normalized = rtrim(rtrim($raw, '0'), '.');
        if ($normalized !== '0' && $normalized !== '1') {
            throw new UnexpectedValueException('Logická hodnota varianty musí být 0 nebo 1.');
        }
        return new VariantMatrixValue($normalized === '1', null);
    }

    private function integer(mixed $value, string $field): int
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

    private function nullableInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->integer($value, $field);
    }

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }
        return $value;
    }

    private function boolean(mixed $value, string $field): bool
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
