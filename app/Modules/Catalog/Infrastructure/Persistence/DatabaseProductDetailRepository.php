<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Infrastructure\\Persistence;

use App\\Modules\\Catalog\\Application\\Dto\\CustomerOptionDetailDto;
use App\\Modules\\Catalog\\Application\\Dto\\CustomerOptionValueDto;
use App\\Modules\\Catalog\\Application\\Dto\\ProductAttributeDetailDto;
use App\\Modules\\Catalog\\Application\\Dto\\ProductDetailDto;
use App\\Modules\\Catalog\\Application\\Dto\\ProductImageDto;
use App\\Modules\\Catalog\\Application\\Dto\\ProductVariantDetailDto;
use App\\Modules\\Catalog\\Application\\Dto\\VariantAttributeValueDto;
use App\\Modules\\Catalog\\Application\\ProductDetailRepository;
use App\\Modules\\Catalog\\Application\\ProductRepository;
use App\\Shared\\Domain\\Locale\\Locale;
use Nette\\Database\\Explorer;
use Nette\\Database\\Table\\ActiveRow;
use UnexpectedValueException;

/**
 * Dávkový read model detailu katalogového produktu.
 *
 * Všechny ActiveRow objekty zůstávají uvnitř persistence vrstvy.
 * Počet dotazů je omezený a nezávisí na počtu variant ani voleb.
 */
final class DatabaseProductDetailRepository implements ProductDetailRepository
{
    public function __construct(
        private readonly Explorer $database,
        private readonly ProductRepository $products,
    ) {}

    public function findActiveDetail(int $productId, Locale $locale): ?ProductDetailDto
    {
        $product = $this->products->findById($productId);

        if ($product === null || $product->status !== 'active') {
            return null;
        }

        $localeCode = $locale->getCode();
        $translation = $this->products->findTranslation($productId, $locale);

        $variantRows = $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        $variantIds = [];
        foreach ($variantRows as $row) {
            $variantIds[] = $this->integer($row->id, 'product_variant.id');
        }

        $variantValueRows = $variantIds === []
            ? []
            : $this->database->table('product_variant_attribute_value')
                ->where('product_variant_id', $variantIds)
                ->fetchAll();

        $productAttributeRows = $this->database->table('product_attribute_value')
            ->where('product_id', $productId)
            ->order('attribute_id ASC, id ASC')
            ->fetchAll();

        $allAttributeIds = [];
        foreach ([$variantValueRows, $productAttributeRows] as $rows) {
            foreach ($rows as $row) {
                $attributeId = $this->integer($row->attribute_id, 'attribute_id');
                $allAttributeIds[$attributeId] = $attributeId;
            }
        }

        $attributeRows = $allAttributeIds === []
            ? []
            : $this->database->table('attribute')
                ->where('id', array_values($allAttributeIds))
                ->fetchAll();

        $attributesById = [];
        foreach ($attributeRows as $row) {
            $id = $this->integer($row->id, 'attribute.id');
            $attributesById[$id] = [
                'code' => $this->string($row->code, 'attribute.code'),
                'type' => $this->string($row->type, 'attribute.type'),
                'unitId' => $this->nullableInteger($row->unit_id, 'attribute.unit_id'),
            ];
        }

        $attributeTranslations = $allAttributeIds === []
            ? []
            : $this->database->table('attribute_translation')
                ->where('attribute_id', array_values($allAttributeIds))
                ->where('locale', $localeCode)
                ->fetchAll();

        $attributeNames = [];
        foreach ($attributeTranslations as $row) {
            $attributeNames[$this->integer($row->attribute_id, 'attribute_translation.attribute_id')] =
                $this->string($row->name, 'attribute_translation.name');
        }

        $enumValueIds = [];
        foreach ([$variantValueRows, $productAttributeRows] as $rows) {
            foreach ($rows as $row) {
                if ($row->attribute_value_id !== null) {
                    $id = $this->integer($row->attribute_value_id, 'attribute_value_id');
                    $enumValueIds[$id] = $id;
                }
            }
        }

        $enumValues = $enumValueIds === []
            ? []
            : $this->database->table('attribute_value')
                ->where('id', array_values($enumValueIds))
                ->fetchAll();

        $enumCodes = [];
        foreach ($enumValues as $row) {
            $enumCodes[$this->integer($row->id, 'attribute_value.id')] =
                $this->string($row->code, 'attribute_value.code');
        }

        $enumTranslations = $enumValueIds === []
            ? []
            : $this->database->table('attribute_value_translation')
                ->where('attribute_value_id', array_values($enumValueIds))
                ->where('locale', $localeCode)
                ->fetchAll();

        $enumLabels = [];
        foreach ($enumTranslations as $row) {
            $enumLabels[$this->integer($row->attribute_value_id, 'attribute_value_translation.attribute_value_id')] =
                $this->string($row->label, 'attribute_value_translation.label');
        }

        $variantValuesByVariant = [];
        foreach ($variantValueRows as $row) {
            $variantId = $this->integer($row->product_variant_id, 'product_variant_attribute_value.product_variant_id');
            $attributeId = $this->integer($row->attribute_id, 'product_variant_attribute_value.attribute_id');
            $attribute = $attributesById[$attributeId] ?? null;

            if ($attribute === null) {
                throw new UnexpectedValueException('Varianta odkazuje na neexistující atribut.');
            }

            $valueId = $this->nullableInteger($row->attribute_value_id, 'product_variant_attribute_value.attribute_value_id');
            $value = $valueId !== null
                ? ($enumCodes[$valueId] ?? null)
                : $this->firstNonNullText($row->value_number, $row->value_text);

            $variantValuesByVariant[$variantId][] = new VariantAttributeValueDto(
                $attributeId,
                $attribute['code'],
                $attributeNames[$attributeId] ?? null,
                $attribute['type'],
                $value,
                $valueId !== null ? ($enumLabels[$valueId] ?? null) : null,
            );
        }

        $variants = [];
        foreach ($variantRows as $row) {
            $id = $this->integer($row->id, 'product_variant.id');
            $variants[] = new ProductVariantDetailDto(
                $id,
                $this->string($row->sku, 'product_variant.sku'),
                $this->boolean($row->is_default, 'product_variant.is_default'),
                $this->nullableInteger($row->weight_g, 'product_variant.weight_g'),
                $this->nullableInteger($row->length_mm, 'product_variant.length_mm'),
                $this->nullableInteger($row->width_mm, 'product_variant.width_mm'),
                $this->nullableInteger($row->height_mm, 'product_variant.height_mm'),
                $this->integer($row->sort_order, 'product_variant.sort_order'),
                $variantValuesByVariant[$id] ?? [],
            );
        }

        $images = [];
        foreach (
            $this->database->table('product_image')
                ->where('product_id', $productId)
                ->order('sort_order ASC, id ASC')
                ->fetchAll() as $row
        ) {
            $images[] = new ProductImageDto(
                $this->integer($row->id, 'product_image.id'),
                $this->nullableInteger($row->product_variant_id, 'product_image.product_variant_id'),
                $this->string($row->file_path, 'product_image.file_path'),
                $this->nullableString($row->alt_text, 'product_image.alt_text'),
                $this->integer($row->sort_order, 'product_image.sort_order'),
            );
        }

        $attributes = [];
        foreach ($productAttributeRows as $row) {
            $attributeId = $this->integer($row->attribute_id, 'product_attribute_value.attribute_id');
            $attribute = $attributesById[$attributeId] ?? null;

            if ($attribute === null) {
                throw new UnexpectedValueException('Produkt odkazuje na neexistující atribut.');
            }

            $valueId = $this->nullableInteger($row->attribute_value_id, 'product_attribute_value.attribute_value_id');
            $attributes[] = new ProductAttributeDetailDto(
                $attributeId,
                $attribute['code'],
                $attributeNames[$attributeId] ?? null,
                $attribute['type'],
                $attribute['unitId'],
                $valueId !== null
                    ? ($enumCodes[$valueId] ?? null)
                    : $this->firstNonNullText($row->value_number, $row->value_text),
                $valueId !== null ? ($enumLabels[$valueId] ?? null) : null,
            );
        }

        $optionRows = $this->database->table('product_option')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        $optionIds = [];
        foreach ($optionRows as $row) {
            $optionIds[] = $this->integer($row->id, 'product_option.id');
        }

        $optionNames = [];
        $optionValuesByOption = [];
        if ($optionIds !== []) {
            foreach (
                $this->database->table('product_option_translation')
                    ->where('product_option_id', $optionIds)
                    ->where('locale', $localeCode)
                    ->fetchAll() as $row
            ) {
                $optionNames[$this->integer($row->product_option_id, 'product_option_translation.product_option_id')] =
                    $this->string($row->name, 'product_option_translation.name');
            }

            $optionValueRows = $this->database->table('product_option_value')
                ->where('product_option_id', $optionIds)
                ->order('sort_order ASC, id ASC')
                ->fetchAll();

            $optionValueIds = [];
            foreach ($optionValueRows as $row) {
                $optionValueIds[] = $this->integer($row->id, 'product_option_value.id');
            }

            $optionValueLabels = [];
            if ($optionValueIds !== []) {
                foreach (
                    $this->database->table('product_option_value_translation')
                        ->where('product_option_value_id', $optionValueIds)
                        ->where('locale', $localeCode)
                        ->fetchAll() as $row
                ) {
                    $optionValueLabels[$this->integer($row->product_option_value_id, 'product_option_value_translation.product_option_value_id')] =
                        $this->string($row->label, 'product_option_value_translation.label');
                }
            }

            foreach ($optionValueRows as $row) {
                $optionId = $this->integer($row->product_option_id, 'product_option_value.product_option_id');
                $valueId = $this->integer($row->id, 'product_option_value.id');
                $optionValuesByOption[$optionId][] = new CustomerOptionValueDto(
                    $valueId,
                    $this->string($row->code, 'product_option_value.code'),
                    $optionValueLabels[$valueId] ?? null,
                    $this->integer($row->sort_order, 'product_option_value.sort_order'),
                );
            }
        }

        $options = [];
        foreach ($optionRows as $row) {
            $id = $this->integer($row->id, 'product_option.id');
            $options[] = new CustomerOptionDetailDto(
                $id,
                $this->string($row->code, 'product_option.code'),
                $this->string($row->type, 'product_option.type'),
                $this->boolean($row->is_required, 'product_option.is_required'),
                $this->integer($row->sort_order, 'product_option.sort_order'),
                $optionNames[$id] ?? null,
                $optionValuesByOption[$id] ?? [],
            );
        }

        return new ProductDetailDto($product, $translation, $variants, $images, $attributes, $options);
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $validated = filter_var($value, FILTER_VALIDATE_INT);
            if ($validated !== false) {
                return $validated;
            }
        }

        if (is_string($value) && preg_match('/\\A-?[0-9]+\\z/D', $value) === 1) {
            $validated = filter_var($value, FILTER_VALIDATE_INT);
            if ($validated !== false) {
                return $validated;
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

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
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

    private function firstNonNullText(mixed $number, mixed $text): ?string
    {
        if ($number !== null) {
            return $this->string($number, 'numeric attribute value');
        }

        return $this->nullableString($text, 'text attribute value');
    }
}
