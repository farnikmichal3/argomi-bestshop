<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\Dto\ProductAdminListing;
use App\Modules\Catalog\Api\Dto\ProductAdminListItem;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use UnexpectedValueException;

/**
 * Dávkové databázové čtení administračního seznamu produktů.
 *
 * Používá Nette Explorer, stránkuje přímo v databázi a překlady i kategorie
 * načítá hromadně pro aktuální stránku. Neomezuje výsledek na aktivní produkty.
 */
final class DatabaseProductAdminListRepository implements ProductAdminListReader
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findProducts(
        ProductAdminListFilter $filter,
        Locale $locale,
    ): ProductAdminListing {
        $selection = $this->database->table('product');

        if ($filter->categoryId !== null) {
            // Poddotaz zůstává součástí Explorer selection: počet i stránka
            // používají totožný filtr a databáze nemusí přenášet všechna ID.
            $selection->where(
                'id IN (SELECT product_id FROM product_category WHERE category_id = ?)',
                $filter->categoryId,
            );
        }

        if ($filter->status !== null) {
            $selection->where('status', $filter->status->value);
        }

        if ($filter->type !== null) {
            $selection->where('type', $filter->type->value);
        }

        $totalItems = $selection->count('*');
        $totalPages = intdiv($totalItems, $filter->perPage)
            + ($totalItems % $filter->perPage === 0 ? 0 : 1);

        $rows = $selection
            ->order('id ASC')
            ->limit($filter->perPage, ($filter->page - 1) * $filter->perPage)
            ->fetchAll();

        if ($rows === []) {
            return new ProductAdminListing(
                [],
                $totalItems,
                $filter->page,
                $filter->perPage,
                $totalPages,
            );
        }

        $productIds = [];
        foreach ($rows as $row) {
            $productIds[] = $this->mapInteger($row->id, 'product.id');
        }

        $productNames = $this->loadProductNames($productIds, $locale);
        $categoryNames = $this->loadCategoryNames($productIds, $locale);

        $items = [];
        foreach ($rows as $row) {
            $productId = $this->mapInteger($row->id, 'product.id');
            $code = $row->code;
            $statusValue = $row->status;
            $typeValue = $row->type;

            if (!is_string($code) || !is_string($statusValue) || !is_string($typeValue)) {
                throw new UnexpectedValueException('Databázový produkt obsahuje neplatný kód, stav nebo typ.');
            }

            $status = ProductStatus::tryFrom($statusValue);
            $type = ProductType::tryFrom($typeValue);

            if ($status === null || $type === null) {
                throw new UnexpectedValueException('Databázový produkt obsahuje nepodporovaný stav nebo typ.');
            }

            $items[] = new ProductAdminListItem(
                $productId,
                $code,
                $productNames[$productId] ?? null,
                $type,
                $status,
                $categoryNames[$productId] ?? [],
            );
        }

        return new ProductAdminListing(
            $items,
            $totalItems,
            $filter->page,
            $filter->perPage,
            $totalPages,
        );
    }

    /**
     * @param list<int> $productIds
     * @return array<int, string>
     */
    private function loadProductNames(array $productIds, Locale $locale): array
    {
        $names = [];

        foreach (
            $this->database->table('product_translation')
                ->where('product_id', $productIds)
                ->where('locale', $locale->getCode())
                ->fetchAll() as $row
        ) {
            $productId = $this->mapInteger($row->product_id, 'product_translation.product_id');
            $name = $row->name;

            if (!is_string($name)) {
                throw new UnexpectedValueException('Překlad produktu neobsahuje platný název.');
            }

            $names[$productId] = $name;
        }

        return $names;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, list<string>>
     */
    private function loadCategoryNames(array $productIds, Locale $locale): array
    {
        $assignments = $this->database->table('product_category')
            ->where('product_id', $productIds)
            ->order('sort_order ASC, category_id ASC')
            ->fetchAll();

        if ($assignments === []) {
            return [];
        }

        /** @var array<int, list<int>> $categoryIdsByProduct */
        $categoryIdsByProduct = [];
        $allCategoryIds = [];

        foreach ($assignments as $assignment) {
            $productId = $this->mapInteger($assignment->product_id, 'product_category.product_id');
            $categoryId = $this->mapInteger($assignment->category_id, 'product_category.category_id');

            $categoryIdsByProduct[$productId] ??= [];
            $categoryIdsByProduct[$productId][] = $categoryId;
            $allCategoryIds[$categoryId] = $categoryId;
        }

        $categoryCodes = [];
        foreach (
            $this->database->table('category')
                ->where('id', array_values($allCategoryIds))
                ->fetchAll() as $row
        ) {
            $categoryId = $this->mapInteger($row->id, 'category.id');
            $code = $row->code;

            if (!is_string($code)) {
                throw new UnexpectedValueException('Databázová kategorie neobsahuje platný kód.');
            }

            $categoryCodes[$categoryId] = $code;
        }

        $localizedNames = [];
        foreach (
            $this->database->table('category_translation')
                ->where('category_id', array_values($allCategoryIds))
                ->where('locale', $locale->getCode())
                ->fetchAll() as $row
        ) {
            $categoryId = $this->mapInteger($row->category_id, 'category_translation.category_id');
            $name = $row->name;

            if (!is_string($name)) {
                throw new UnexpectedValueException('Překlad kategorie neobsahuje platný název.');
            }

            $localizedNames[$categoryId] = $name;
        }

        $namesByProduct = [];
        foreach ($categoryIdsByProduct as $productId => $categoryIds) {
            $names = [];

            foreach ($categoryIds as $categoryId) {
                $names[] = $localizedNames[$categoryId] ?? $categoryCodes[$categoryId] ?? (string) $categoryId;
            }

            $namesByProduct[$productId] = $names;
        }

        return $namesByProduct;
    }

    private function mapInteger(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException(sprintf(
            'Databázové pole %s neobsahuje platné PHP celé číslo.',
            $field,
        ));
    }
}
