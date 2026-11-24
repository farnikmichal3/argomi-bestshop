<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Api\Dto\ProductVariantEditorData;
use App\Modules\Catalog\Api\ProductVariantEditorReader;
use App\Modules\Catalog\Application\ProductVariantWriteRepository;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use UnexpectedValueException;

final readonly class DatabaseProductVariantEditorRepository implements ProductVariantEditorReader, ProductVariantWriteRepository
{
    public function __construct(private Explorer $database) {}

    public function findForEdit(int $variantId, Locale $locale): ?ProductVariantEditorData
    {
        $variant = $this->database->table('product_variant')->where('id', $variantId)->fetch();
        if ($variant === null) {
            return null;
        }

        $productId = self::toInt($variant->product_id, 'product_variant.product_id');
        $product = $this->database->table('product')->where('id', $productId)->fetch();
        if ($product === null || !is_string($product->code) || !is_string($variant->sku)) {
            throw new UnexpectedValueException('Varianta odkazuje na neplatný produkt nebo obsahuje neplatné údaje.');
        }

        $translation = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $locale->getCode())
            ->fetch();
        $productName = $translation?->name;
        if ($productName !== null && !is_string($productName)) {
            throw new UnexpectedValueException('Název produktu varianty není platný text.');
        }

        $ean = $variant->ean;
        if ($ean !== null && !is_string($ean)) {
            throw new UnexpectedValueException('EAN varianty není platný text.');
        }

        return new ProductVariantEditorData(
            self::toInt($variant->id, 'product_variant.id'),
            $productId,
            $product->code,
            $productName,
            $variant->sku,
            $ean,
            self::nullableInt($variant->weight_g, 'product_variant.weight_g'),
            self::toBool($variant->is_default, 'product_variant.is_default'),
            self::toBool($variant->is_active, 'product_variant.is_active'),
        );
    }

    public function variantExists(int $variantId): bool
    {
        return $this->database->table('product_variant')->where('id', $variantId)->fetch() !== null;
    }

    public function findProductId(int $variantId): ?int
    {
        $value = $this->database->table('product_variant')->where('id', $variantId)->fetch()?->product_id;
        return $value === null ? null : self::toInt($value, 'product_variant.product_id');
    }

    public function isVariantActive(int $variantId): bool
    {
        $value = $this->database->table('product_variant')->where('id', $variantId)->fetch()?->is_active;
        return $value !== null && self::toBool($value, 'product_variant.is_active');
    }

    public function isSkuAvailable(string $sku, int $excludeVariantId): bool
    {
        return $this->database->table('product_variant')
            ->where('sku', $sku)
            ->where('id != ?', $excludeVariantId)
            ->fetch() === null;
    }

    public function isEanAvailable(string $ean, int $excludeVariantId): bool
    {
        return $this->database->table('product_variant')
            ->where('ean', $ean)
            ->where('id != ?', $excludeVariantId)
            ->fetch() === null;
    }

    public function updateVariant(int $variantId, string $sku, ?string $ean, ?int $weightG): void
    {
        $this->database->table('product_variant')->where('id', $variantId)->update([
            'sku' => $sku,
            'ean' => $ean,
            'weight_g' => $weightG,
        ]);
    }

    public function clearDefaultVariant(int $productId): void
    {
        $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);
    }

    public function setDefaultVariant(int $variantId): void
    {
        $this->database->table('product_variant')
            ->where('id', $variantId)
            ->update(['is_default' => 1]);
    }

    public function unsetDefaultVariant(int $variantId): void
    {
        $this->database->table('product_variant')
            ->where('id', $variantId)
            ->update(['is_default' => 0]);
    }

    private static function toInt(mixed $value, string $field): int
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

    private static function nullableInt(mixed $value, string $field): ?int
    {
        return $value === null ? null : self::toInt($value, $field);
    }

    private static function toBool(mixed $value, string $field): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }
        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platnou logickou hodnotu.', $field));
    }
}
