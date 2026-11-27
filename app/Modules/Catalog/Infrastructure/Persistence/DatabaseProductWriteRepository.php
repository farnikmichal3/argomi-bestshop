<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\ProductWriteRepository;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final class DatabaseProductWriteRepository implements ProductWriteRepository
{
    public function __construct(private readonly Explorer $database) {}

    public function isCodeAvailable(string $code, ?int $excludeProductId = null): bool
    {
        $selection = $this->database->table('product')->where('code', $code);
        if ($excludeProductId !== null) {
            $selection->where('id != ?', $excludeProductId);
        }
        return $selection->fetch() === null;
    }

    public function productExists(int $productId): bool
    {
        return $this->database->table('product')->where('id', $productId)->fetch() !== null;
    }

    public function hasPublishableVariantConfiguration(int $productId): bool
    {
        $variants = $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('is_active', 1);

        if ($variants->count('*') < 1) {
            return false;
        }

        // Databáze vynucuje nejvýše jednu výchozí variantu; ověříme,
        // že právě jedna výchozí varianta patří mezi aktivní varianty.
        return $this->database->table('product_variant')
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->count('*') === 1;
    }

    public function taxClassExists(int $taxClassId): bool
    {
        return $this->database->table('tax_class')->where('id', $taxClassId)->fetch() !== null;
    }

    public function categoryExists(int $categoryId): bool
    {
        return $this->database->table('category')->where('id', $categoryId)->fetch() !== null;
    }

    public function createProduct(string $code, ProductStatus $status, ProductType $type, int $taxClassId): int
    {
        $row = $this->database->table('product')->insert([
            'code' => $code,
            'status' => $status->value,
            'type' => $type->value,
            'tax_class_id' => $taxClassId,
        ]);
        if (!$row instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nevrátila řádek nového produktu.');
        }
        return self::toInt($row->id, 'product.id');
    }

    public function updateProduct(int $productId, string $code, ProductStatus $status, ProductType $type, int $taxClassId): void
    {
        $this->database->table('product')->where('id', $productId)->update([
            'code' => $code,
            'status' => $status->value,
            'type' => $type->value,
            'tax_class_id' => $taxClassId,
        ]);
    }

    public function saveTranslation(
        int $productId,
        Locale $locale,
        string $name,
        string $slug,
        ?string $shortDescription,
        ?string $description,
        ?string $metaTitle,
        ?string $metaDescription,
    ): void {
        $selection = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $locale->getCode());
        $values = [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ];
        if ($selection->fetch() === null) {
            $this->database->table('product_translation')->insert($values + [
                'product_id' => $productId,
                'locale' => $locale->getCode(),
            ]);
            return;
        }
        $selection->update($values);
    }

    /** @param list<int> $categoryIds */
    public function replaceCategories(int $productId, array $categoryIds): void
    {
        $this->database->table('product_category')->where('product_id', $productId)->delete();
        foreach ($categoryIds as $position => $categoryId) {
            $this->database->table('product_category')->insert([
                'product_id' => $productId,
                'category_id' => $categoryId,
                'sort_order' => $position,
            ]);
        }
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
        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné PHP celé číslo.', $field));
    }
}
