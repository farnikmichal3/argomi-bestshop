<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Infrastructure\\Persistence;

use App\\Modules\\Catalog\\Application\\CategoryRepository;
use App\\Modules\\Catalog\\Application\\Dto\\CategoryTranslationDto;
use App\\Modules\\Catalog\\Application\\Dto\\CategoryTreeDto;
use App\\Modules\\Catalog\\Domain\\Category\\Category;
use App\\Shared\\Domain\\Locale\\Locale;
use Nette\\Database\\Explorer;
use Nette\\Database\\Table\\ActiveRow;
use UnexpectedValueException;

/**
 * Databázové čtení stromu kategorií přes Nette Database Explorer.
 *
 * Kategorie a jejich překlady se načítají dávkově; mapování ActiveRow na
 * doménové objekty a DTO zůstává výhradně v persistence vrstvě.
 */
final class DatabaseCategoryRepository implements CategoryRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findTree(Locale $locale): array
    {
        return $this->loadTree($locale, false);
    }

    public function findActiveTree(Locale $locale): array
    {
        return $this->loadTree($locale, true);
    }

    /**
     * @return list<CategoryTreeDto>
     */
    private function loadTree(Locale $locale, bool $activeOnly): array
    {
        $rows = $this->database->table('category')
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        /** @var array<int, Category> $categories */
        $categories = [];
        /** @var array<int, list<int>> $childrenByParent */
        $childrenByParent = [];

        foreach ($rows as $row) {
            $category = $this->mapCategory($row);

            if ($activeOnly && !$category->isActive) {
                continue;
            }

            $categories[$category->id] = $category;
            $parentKey = $category->parentId ?? 0;
            $childrenByParent[$parentKey] ??= [];
            $childrenByParent[$parentKey][] = $category->id;
        }

        $translationRows = $this->database->table('category_translation')
            ->where('locale', $locale->getCode())
            ->fetchAll();

        /** @var array<int, CategoryTranslationDto> $translations */
        $translations = [];

        foreach ($translationRows as $row) {
            $translation = $this->mapTranslation($row, $locale);

            if (isset($categories[$translation->categoryId])) {
                $translations[$translation->categoryId] = $translation;
            }
        }

        $visited = [];
        $tree = $this->buildChildren(0, $childrenByParent, $categories, $translations, $visited);

        if (count($visited) !== count($categories)) {
            throw new UnexpectedValueException('Strom kategorií obsahuje cyklus nebo osiřelou kategorii.');
        }

        return $tree;
    }

    /**
     * @param array<int, list<int>> $childrenByParent
     * @param array<int, Category> $categories
     * @param array<int, CategoryTranslationDto> $translations
     * @param array<int, true> $visited
     * @return list<CategoryTreeDto>
     */
    private function buildChildren(
        int $parentId,
        array $childrenByParent,
        array $categories,
        array $translations,
        array &$visited,
    ): array {
        $result = [];

        foreach ($childrenByParent[$parentId] ?? [] as $categoryId) {
            if (isset($visited[$categoryId])) {
                throw new UnexpectedValueException('Strom kategorií obsahuje cyklus.');
            }

            $visited[$categoryId] = true;
            $result[] = new CategoryTreeDto(
                $categories[$categoryId],
                $translations[$categoryId] ?? null,
                $this->buildChildren($categoryId, $childrenByParent, $categories, $translations, $visited),
            );
        }

        return $result;
    }

    private function mapCategory(ActiveRow $row): Category
    {
        $code = $row->code;
        $active = $row->is_active;

        if (!is_string($code) || !in_array($active, [0, 1, '0', '1', false, true], true)) {
            throw new UnexpectedValueException('Databázová kategorie obsahuje neplatný kód nebo příznak aktivity.');
        }

        return new Category(
            $this->mapInteger($row->id, 'category.id'),
            $this->mapNullableInteger($row->parent_id, 'category.parent_id'),
            $code,
            $this->mapInteger($row->sort_order, 'category.sort_order'),
            (bool) $active,
        );
    }

    private function mapTranslation(ActiveRow $row, Locale $locale): CategoryTranslationDto
    {
        $name = $row->name;
        $slug = $row->slug;

        if (!is_string($name) || !is_string($slug)) {
            throw new UnexpectedValueException('Překlad kategorie obsahuje neplatný název nebo slug.');
        }

        return new CategoryTranslationDto(
            $this->mapInteger($row->category_id, 'category_translation.category_id'),
            $locale,
            $name,
            $slug,
            $this->mapNullableString($row->description, 'category_translation.description'),
        );
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

        // sort_order is signed and may be negative.
        if ($field === 'category.sort_order' && is_string($value) && preg_match('/\\A-?[0-9]+\\z/D', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné PHP celé číslo.', $field));
    }

    private function mapNullableInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->mapInteger($value, $field);
    }

    private function mapNullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }
}
