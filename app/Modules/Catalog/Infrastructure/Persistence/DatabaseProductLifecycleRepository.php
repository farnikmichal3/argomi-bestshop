<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductLifecycleRepository;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Persistence životního cyklu produktu.
 *
 * Kopie dostává nové identity. Znovupoužitelné katalogové vazby se přemapují,
 * zatímco obchodní historie, unikátní identifikátory a vztahy k jiným produktům
 * zůstávají výhradně u původního produktu.
 */
final readonly class DatabaseProductLifecycleRepository implements ProductLifecycleRepository
{
    public function __construct(private Explorer $database) {}

    public function findStatus(int $productId): ?ProductStatus
    {
        $value = $this->database->table('product')->get($productId)?->status;
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || ProductStatus::tryFrom($value) === null) {
            throw new UnexpectedValueException('Produkt obsahuje neplatný stav životního cyklu.');
        }
        return ProductStatus::from($value);
    }

    public function archiveProduct(int $productId): void
    {
        $this->database->table('product')
            ->where('id', $productId)
            ->update(['status' => ProductStatus::Archived->value]);
    }

    public function cloneArchivedProduct(int $productId): int
    {
        $source = $this->database->table('product')->get($productId);
        if (!$source instanceof ActiveRow || $source->status !== ProductStatus::Archived->value) {
            throw new UnexpectedValueException('Kopírovat lze pouze existující archivovaný produkt.');
        }

        $newCode = $this->createUniqueProductCode($this->string($source->code, 'product.code'));
        $newProduct = $this->database->table('product')->insert([
            'code' => $newCode,
            'status' => ProductStatus::Draft->value,
            'type' => $this->string($source->type, 'product.type'),
            'tax_class_id' => $source->tax_class_id,
        ]);
        if (!$newProduct instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nepotvrdila vytvoření kopie produktu.');
        }
        $newProductId = $this->integer($newProduct->id, 'product.id');

        $this->copyTranslations($productId, $newProductId);
        $this->copyCategories($productId, $newProductId);
        $this->copyProductAttributes($productId, $newProductId);
        $this->copyVariantAxes($productId, $newProductId);
        $variantIds = $this->copyVariants($productId, $newProductId);
        $this->copyVariantAttributeValues($productId, $newProductId, $variantIds);
        $this->copyVariantPrices($variantIds);
        $this->copyImages($productId, $newProductId, $variantIds);
        $this->copyCustomerOptions($productId, $newProductId);
        $this->copyStockSettings($variantIds);

        // product_relation se záměrně nekopíruje: vazby jsou směrové a
        // nové identitě produktu by mohly přenést obchodně neplatná doporučení.
        // Objednávky, košíky, sklad a historie se rovněž nikdy nekopírují.
        return $newProductId;
    }

    private function createUniqueProductCode(string $sourceCode): string
    {
        for ($suffix = 1; $suffix <= 10000; $suffix++) {
            $ending = '-copy' . ($suffix === 1 ? '' : '-' . $suffix);
            $candidate = substr($sourceCode, 0, 100 - strlen($ending)) . $ending;
            if ($this->database->table('product')->where('code', $candidate)->fetch() === null) {
                return $candidate;
            }
        }
        throw new UnexpectedValueException('Nepodařilo se vytvořit jedinečný kód kopie produktu.');
    }

    private function copyTranslations(int $sourceId, int $targetId): void
    {
        foreach ($this->database->table('product_translation')->where('product_id', $sourceId)->fetchAll() as $row) {
            $locale = $this->string($row->locale, 'product_translation.locale');
            $slug = $this->string($row->slug, 'product_translation.slug');
            $suffix = '-copy-' . $targetId;
            $this->database->table('product_translation')->insert([
                'product_id' => $targetId,
                'locale' => $locale,
                'name' => $row->name,
                'slug' => substr($slug, 0, 255 - strlen($suffix)) . $suffix,
                'short_description' => $row->short_description,
                'description' => $row->description,
                'meta_title' => $row->meta_title,
                'meta_description' => $row->meta_description,
            ]);
        }
    }

    private function copyCategories(int $sourceId, int $targetId): void
    {
        foreach ($this->database->table('product_category')->where('product_id', $sourceId)->fetchAll() as $row) {
            $this->database->table('product_category')->insert([
                'product_id' => $targetId,
                'category_id' => $row->category_id,
                'sort_order' => $row->sort_order,
            ]);
        }
    }

    private function copyProductAttributes(int $sourceId, int $targetId): void
    {
        foreach ($this->database->table('product_attribute_value')->where('product_id', $sourceId)->fetchAll() as $row) {
            $this->database->table('product_attribute_value')->insert([
                'product_id' => $targetId,
                'attribute_id' => $row->attribute_id,
                'attribute_value_id' => $row->attribute_value_id,
                'value_number' => $row->value_number,
                'value_text' => $row->value_text,
            ]);
        }
    }

    private function copyVariantAxes(int $sourceId, int $targetId): void
    {
        foreach ($this->database->table('product_variant_axis')
            ->where('product_id', $sourceId)
            ->order('sort_order ASC, attribute_id ASC')
            ->fetchAll() as $row) {
            $this->database->table('product_variant_axis')->insert([
                'product_id' => $targetId,
                'attribute_id' => $row->attribute_id,
                'sort_order' => $row->sort_order,
            ]);
        }
    }

    /** @return array<int, int> mapování původního ID varianty na nové ID */
    private function copyVariants(int $sourceId, int $targetId): array
    {
        $mapping = [];
        $rows = $this->database->table('product_variant')
            ->where('product_id', $sourceId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        foreach ($rows as $index => $row) {
            $variant = $this->database->table('product_variant')->insert([
                'product_id' => $targetId,
                'sku' => sprintf('COPY-%d-%d', $targetId, $index + 1),
                'combination_hash' => $this->string($row->combination_hash, 'product_variant.combination_hash'),
                'is_default' => $row->is_default,
                'is_active' => 0,
                'weight_g' => $row->weight_g,
                'length_mm' => $row->length_mm,
                'width_mm' => $row->width_mm,
                'height_mm' => $row->height_mm,
                'sort_order' => $row->sort_order,
            ]);
            if (!$variant instanceof ActiveRow) {
                throw new UnexpectedValueException('Databáze nepotvrdila vytvoření kopie varianty.');
            }
            $mapping[$this->integer($row->id, 'product_variant.id')] = $this->integer($variant->id, 'product_variant.id');
        }

        return $mapping;
    }

    /** @param array<int, int> $variantIds */
    private function copyVariantAttributeValues(int $sourceId, int $targetId, array $variantIds): void
    {
        foreach ($this->database->table('product_variant_attribute_value')->where('product_id', $sourceId)->fetchAll() as $row) {
            $sourceVariantId = $this->integer($row->product_variant_id, 'product_variant_attribute_value.product_variant_id');
            $targetVariantId = $variantIds[$sourceVariantId] ?? null;
            if ($targetVariantId === null) {
                throw new UnexpectedValueException('Hodnota atributu odkazuje na variantu mimo kopírovaný produkt.');
            }
            $this->database->table('product_variant_attribute_value')->insert([
                'product_variant_id' => $targetVariantId,
                'product_id' => $targetId,
                'attribute_id' => $row->attribute_id,
                'attribute_value_id' => $row->attribute_value_id,
                'value_number' => $row->value_number,
                'value_text' => $row->value_text,
            ]);
        }
    }

    /** @param array<int, int> $variantIds */
    private function copyVariantPrices(array $variantIds): void
    {
        foreach ($variantIds as $sourceVariantId => $targetVariantId) {
            foreach ($this->database->table('variant_price')
                ->where('product_variant_id', $sourceVariantId)->fetchAll() as $price) {
                $this->database->table('variant_price')->insert([
                    'product_variant_id' => $targetVariantId,
                    'currency' => $price->currency,
                    'amount_minor' => $price->amount_minor,
                    'tax_included' => $price->tax_included,
                ]);
            }
        }
    }

    /** @param array<int, int> $variantIds */
    private function copyStockSettings(array $variantIds): void
    {
        foreach ($variantIds as $sourceVariantId => $targetVariantId) {
            $stock = $this->database->table('stock_item')
                ->where('product_variant_id', $sourceVariantId)->fetch();
            if ($stock === null) {
                continue;
            }

            $this->database->table('stock_item')->insert([
                'product_variant_id' => $targetVariantId,
                'track_stock' => $stock->track_stock,
                'allow_backorder' => $stock->allow_backorder,
                'quantity_on_hand' => '0.000',
                'quantity_reserved' => '0.000',
            ]);
        }
    }

    /** @param array<int, int> $variantIds */
    private function copyImages(int $sourceId, int $targetId, array $variantIds): void
    {
        $images = $this->database->table('product_image')
            ->where('product_id', $sourceId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        foreach ($images as $row) {
            $sourceVariantId = $row->product_variant_id === null
                ? null
                : $this->integer($row->product_variant_id, 'product_image.product_variant_id');
            $targetVariantId = $sourceVariantId === null ? null : ($variantIds[$sourceVariantId] ?? null);
            if ($sourceVariantId !== null && $targetVariantId === null) {
                throw new UnexpectedValueException('Obrázek odkazuje na variantu mimo kopírovaný produkt.');
            }

            $image = $this->database->table('product_image')->insert([
                'product_id' => $targetId,
                'media_asset_id' => $row->media_asset_id,
                'product_variant_id' => $targetVariantId,
                'file_path' => $row->file_path,
                'alt_text' => $row->alt_text,
                'sort_order' => $row->sort_order,
                'is_primary' => $row->is_primary,
                'source_sha256' => $row->source_sha256,
                'source_mime_type' => $row->source_mime_type,
                'source_width' => $row->source_width,
                'source_height' => $row->source_height,
                'source_file_size_bytes' => $row->source_file_size_bytes,
            ]);
            if (!$image instanceof ActiveRow) {
                throw new UnexpectedValueException('Databáze nepotvrdila vytvoření kopie obrázku produktu.');
            }

            $newImageId = $this->integer($image->id, 'product_image.id');
            foreach ($this->database->table('product_image_derivative')
                ->where('product_image_id', $this->integer($row->id, 'product_image.id'))
                ->fetchAll() as $derivative) {
                $this->database->table('product_image_derivative')->insert([
                    'product_image_id' => $newImageId,
                    'format' => $derivative->format,
                    'source_sha256' => $derivative->source_sha256,
                    'recipe_sha256' => $derivative->recipe_sha256,
                    'width' => $derivative->width,
                    'height' => $derivative->height,
                    'file_path' => $derivative->file_path,
                    'file_size_bytes' => $derivative->file_size_bytes,
                ]);
            }
        }
    }

    private function copyCustomerOptions(int $sourceId, int $targetId): void
    {
        $optionIds = [];
        foreach ($this->database->table('product_option')
            ->where('product_id', $sourceId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll() as $row) {
            $option = $this->database->table('product_option')->insert([
                'product_id' => $targetId,
                'code' => $row->code,
                'type' => $row->type,
                'is_required' => $row->is_required,
                'sort_order' => $row->sort_order,
            ]);
            if (!$option instanceof ActiveRow) {
                throw new UnexpectedValueException('Databáze nepotvrdila kopii zákaznické volby.');
            }
            $sourceOptionId = $this->integer($row->id, 'product_option.id');
            $targetOptionId = $this->integer($option->id, 'product_option.id');
            $optionIds[$sourceOptionId] = $targetOptionId;

            foreach ($this->database->table('product_option_translation')
                ->where('product_option_id', $sourceOptionId)->fetchAll() as $translation) {
                $this->database->table('product_option_translation')->insert([
                    'product_option_id' => $targetOptionId,
                    'locale' => $translation->locale,
                    'name' => $translation->name,
                ]);
            }
        }

        foreach ($optionIds as $sourceOptionId => $targetOptionId) {
            $valueIds = [];
            foreach ($this->database->table('product_option_value')
                ->where('product_option_id', $sourceOptionId)
                ->order('sort_order ASC, id ASC')
                ->fetchAll() as $row) {
                $value = $this->database->table('product_option_value')->insert([
                    'product_option_id' => $targetOptionId,
                    'code' => $row->code,
                    'sort_order' => $row->sort_order,
                ]);
                if (!$value instanceof ActiveRow) {
                    throw new UnexpectedValueException('Databáze nepotvrdila kopii hodnoty zákaznické volby.');
                }
                $valueIds[$this->integer($row->id, 'product_option_value.id')] = $this->integer($value->id, 'product_option_value.id');
            }

            foreach ($valueIds as $sourceValueId => $targetValueId) {
                foreach ($this->database->table('option_value_price')
                    ->where('product_option_value_id', $sourceValueId)->fetchAll() as $price) {
                    $this->database->table('option_value_price')->insert([
                        'product_option_value_id' => $targetValueId,
                        'currency' => $price->currency,
                        'delta_minor' => $price->delta_minor,
                    ]);
                }

                foreach ($this->database->table('product_option_value_translation')
                    ->where('product_option_value_id', $sourceValueId)->fetchAll() as $translation) {
                    $this->database->table('product_option_value_translation')->insert([
                        'product_option_value_id' => $targetValueId,
                        'locale' => $translation->locale,
                        'label' => $translation->label,
                    ]);
                }
            }
        }
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

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }
        return $value;
    }
}
