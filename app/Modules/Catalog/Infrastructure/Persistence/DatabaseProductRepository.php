<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductDto;
use App\Modules\Catalog\Application\Dto\ProductListingDto;
use App\Modules\Catalog\Application\Dto\ProductListItemDto;
use App\Modules\Catalog\Application\Dto\ProductTranslationDto;
use App\Modules\Catalog\Application\ProductRepository;
use App\Modules\Catalog\Application\ProductSortOrder;
use App\Shared\Domain\Locale\Locale;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Databázová implementace čtecího kontraktu katalogu.
 *
 * Veškeré dotazy používají Nette Database Explorer. ActiveRow se mapuje
 * na typované DTO přímo v této třídě a nikdy neopouští persistence vrstvu.
 */
final class DatabaseProductRepository implements ProductRepository
{
    private readonly DateTimeZone $utc;

    public function __construct(
        private readonly Explorer $database,
    ) {
        $this->utc = new DateTimeZone('UTC');
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?ProductDto
    {
        $row = $this->database->table('product')
            ->where('id', $id)
            ->fetch();

        return $row === null ? null : $this->mapProduct($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?ProductDto
    {
        $row = $this->database->table('product')
            ->where('code', $code)
            ->fetch();

        return $row === null ? null : $this->mapProduct($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation(int $productId, Locale $locale): ?ProductTranslationDto
    {
        $row = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $locale->getCode())
            ->fetch();

        return $row === null ? null : $this->mapTranslation($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslations(int $productId): array
    {
        $rows = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->order('locale ASC')
            ->fetchAll();

        $translations = [];

        foreach ($rows as $row) {
            $translations[] = $this->mapTranslation($row);
        }

        return $translations;
    }

    /**
     * {@inheritDoc}
     */
    public function findByCategory(
        int $categoryId,
        Locale $locale,
        int $page = 1,
        int $perPage = 12,
        ProductSortOrder $sort = ProductSortOrder::CategoryPosition,
    ): ProductListingDto {
        if ($categoryId < 1 || $page < 1 || $perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('ID kategorie a stránkování musí být kladné; limit stránky je 100.');
        }

        if ($page > intdiv(PHP_INT_MAX, $perPage)) {
            throw new \InvalidArgumentException('Požadovaná stránka překračuje bezpečný rozsah offsetu.');
        }

        $category = $this->database->table('category')
            ->where('id', $categoryId)
            ->where('is_active', 1)
            ->fetch();

        if ($category === null) {
            return new ProductListingDto([], 0, $page, $perPage, 0);
        }

        // Načteme přímá přiřazení a aktivní produkty samostatně.
        // Tím se vyhneme implicitnímu FK resolveru Nette Exploreru,
        // jehož dostupnost závisí na introspekci konkrétního DB driveru.
        $assignmentRows = $this->database->table('product_category')
            ->where('category_id', $categoryId)
            ->fetchAll();

        if ($assignmentRows === []) {
            return new ProductListingDto([], 0, $page, $perPage, 0);
        }

        $assignmentsByProductId = [];
        $productIds = [];

        foreach ($assignmentRows as $assignment) {
            $productId = $this->mapInteger($assignment->product_id, 'product_category.product_id');
            $assignmentsByProductId[$productId] = $this->mapInteger(
                $assignment->sort_order,
                'product_category.sort_order',
            );
            $productIds[] = $productId;
        }

        $productsById = [];
        foreach (
            $this->database->table('product')
                ->where('id', $productIds)
                ->where('status', 'active')
                ->fetchAll() as $row
        ) {
            $product = $this->mapProduct($row);
            $productsById[$product->id] = $product;
        }

        // Produkt bez odpovídajícího řádku je při správně vynuceném FK
        // nemožný; neaktivní produkty se z katalogu pouze vyřadí.
        $listing = [];
        foreach ($productsById as $productId => $product) {
            $listing[] = [
                'product' => $product,
                'categoryPosition' => $assignmentsByProductId[$productId],
            ];
        }

        usort($listing, static function (array $left, array $right) use ($sort): int {
            /** @var ProductDto $leftProduct */
            $leftProduct = $left['product'];
            /** @var ProductDto $rightProduct */
            $rightProduct = $right['product'];

            return match ($sort) {
                ProductSortOrder::CategoryPosition
                    =>
                    ($positionComparison = $left['categoryPosition'] <=> $right['categoryPosition']) !== 0
                        ? $positionComparison
                        : ($leftProduct->id <=> $rightProduct->id),
                ProductSortOrder::ProductCodeAscending
                    =>
                    ($codeComparison = strcmp($leftProduct->code, $rightProduct->code)) !== 0
                        ? $codeComparison
                        : ($leftProduct->id <=> $rightProduct->id),
                ProductSortOrder::ProductCodeDescending
                    =>
                    ($codeComparison = strcmp($rightProduct->code, $leftProduct->code)) !== 0
                        ? $codeComparison
                        : ($rightProduct->id <=> $leftProduct->id),
                ProductSortOrder::Newest
                    =>
                    ($dateComparison = $rightProduct->createdAt <=> $leftProduct->createdAt) !== 0
                        ? $dateComparison
                        : ($rightProduct->id <=> $leftProduct->id),
                ProductSortOrder::Oldest
                    =>
                    ($dateComparison = $leftProduct->createdAt <=> $rightProduct->createdAt) !== 0
                        ? $dateComparison
                        : ($leftProduct->id <=> $rightProduct->id),
            };
        });

        $totalItems = count($listing);
        $totalPages = intdiv($totalItems, $perPage) + ($totalItems % $perPage === 0 ? 0 : 1);
        $pageItems = array_slice($listing, ($page - 1) * $perPage, $perPage);

        if ($pageItems === []) {
            return new ProductListingDto([], $totalItems, $page, $perPage, $totalPages);
        }

        $pageProductIds = [];
        foreach ($pageItems as $item) {
            $pageProductIds[] = $item['product']->id;
        }

        $translationsByProductId = [];
        foreach (
            $this->database->table('product_translation')
                ->where('product_id', $pageProductIds)
                ->where('locale', $locale->getCode())
                ->fetchAll() as $row
        ) {
            $translation = $this->mapTranslation($row);
            $translationsByProductId[$translation->productId] = $translation;
        }

        $items = [];
        foreach ($pageItems as $item) {
            /** @var ProductDto $product */
            $product = $item['product'];
            $items[] = new ProductListItemDto(
                $product,
                $translationsByProductId[$product->id] ?? null,
            );
        }

        return new ProductListingDto($items, $totalItems, $page, $perPage, $totalPages);
    }

    /**
     * Převede produktový řádek na DTO a ověří očekávané databázové typy.
     *
     * Kontrola je záměrná i při existenci SQL omezení: poškozená nebo
     * neočekávaná data se mají projevit na hranici persistence.
     */
    private function mapProduct(ActiveRow $row): ProductDto
    {
        $status = $row->status;
        $code = $row->code;

        if (!is_string($status) || !is_string($code)) {
            throw new UnexpectedValueException('Databázový produkt obsahuje neplatný stav nebo kód.');
        }

        return new ProductDto(
            $this->mapInteger($row->id, 'product.id'),
            $code,
            $status,
            $this->mapInteger($row->tax_class_id, 'product.tax_class_id'),
            $this->mapDateTime($row->created_at, 'product.created_at'),
            $this->mapDateTime($row->updated_at, 'product.updated_at'),
        );
    }

    /**
     * Převede překladový řádek na DTO bez úniku ActiveRow.
     */
    private function mapTranslation(ActiveRow $row): ProductTranslationDto
    {
        $locale = $row->locale;
        $name = $row->name;
        $slug = $row->slug;

        if (!is_string($locale) || !is_string($name) || !is_string($slug)) {
            throw new UnexpectedValueException('Databázový překlad produktu obsahuje neplatný jazyk, název nebo slug.');
        }

        return new ProductTranslationDto(
            $this->mapInteger($row->id, 'product_translation.id'),
            $this->mapInteger($row->product_id, 'product_translation.product_id'),
            Locale::fromString($locale),
            $name,
            $slug,
            $this->mapNullableString($row->short_description, 'product_translation.short_description'),
            $this->mapNullableString($row->description, 'product_translation.description'),
            $this->mapNullableString($row->meta_title, 'product_translation.meta_title'),
            $this->mapNullableString($row->meta_description, 'product_translation.meta_description'),
        );
    }

    /**
     * Převede celočíselný databázový údaj včetně číselného řetězce na PHP int.
     *
     * BIGINT UNSIGNED může být v závislosti na ovladači vrácen jako int
     * nebo jako řetězec. Hodnoty mimo rozsah PHP int odmítneme místo tichého
     * přetečení, protože by změnilo identitu záznamu.
     */
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

        if (
            in_array($field, ['product_category.sort_order'], true)
            && is_string($value)
            && preg_match('/\\A-?[0-9]+\\z/D', $value) === 1
        ) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné PHP celé číslo.', $field));
    }

    /**
     * Převede volitelný textový sloupec a odmítne neočekávaný typ.
     */
    private function mapNullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }

    /**
     * Převede DATETIME z databáze na neměnný okamžik v UTC.
     *
     * MySQL DATETIME neobsahuje informaci o časovém pásmu; projekt jej
     * ukládá v UTC, proto se řetězcová hodnota interpretuje výslovně jako UTC.
     *
     * @throws UnexpectedValueException Pokud datum nelze bezpečně převést.
     */
    private function mapDateTime(mixed $value, string $field): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            // DATETIME nemá časové pásmo. Zachováme uložený čas na ciferníku
            // a znovu jej interpretujeme jako UTC; pouhé setTimezone() by
            // mohlo posunout hodnotu podle výchozí zóny PDO/PHP.
            return new DateTimeImmutable($value->format('Y-m-d H:i:s.u'), $this->utc);
        }

        if (is_string($value)) {
            try {
                return new DateTimeImmutable($value, $this->utc);
            } catch (Exception $exception) {
                throw new UnexpectedValueException(
                    sprintf('Databázové pole %s neobsahuje platné datum a čas.', $field),
                    previous: $exception,
                );
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné datum a čas.', $field));
    }
}
