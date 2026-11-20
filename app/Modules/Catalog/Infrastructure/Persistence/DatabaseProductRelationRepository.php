<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductRelationDto;
use App\Modules\Catalog\Application\Dto\ProductTranslationDto;
use App\Modules\Catalog\Application\ProductRelationRepository;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use UnexpectedValueException;

/**
 * Dávkové načítání směrových vztahů produktů bez N+1 dotazů.
 */
final class DatabaseProductRelationRepository implements ProductRelationRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findForProduct(int $productId, Locale $locale): array
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('ID produktu musí být kladné.');
        }

        $relationRows = $this->database->table('product_relation')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        if ($relationRows === []) {
            return [];
        }

        $relations = [];
        $relatedProductIds = [];

        foreach ($relationRows as $row) {
            $relatedProductId = $this->integer($row->related_product_id, 'product_relation.related_product_id');

            // Sebevazba není pro katalogový výpis užitečná ani při ruční
            // nebo historické manipulaci s databází.
            if ($relatedProductId === $productId) {
                continue;
            }

            $relations[] = [
                'productId' => $relatedProductId,
                'type' => $this->string($row->type, 'product_relation.type'),
                'sortOrder' => $this->integer($row->sort_order, 'product_relation.sort_order'),
            ];
            $relatedProductIds[$relatedProductId] = $relatedProductId;
        }

        if ($relations === []) {
            return [];
        }

        $productsById = [];
        foreach (
            $this->database->table('product')
                ->where('id', array_values($relatedProductIds))
                ->where('status', 'active')
                ->fetchAll() as $row
        ) {
            $id = $this->integer($row->id, 'product.id');
            $productsById[$id] = $this->string($row->code, 'product.code');
        }

        $activeIds = array_keys($productsById);
        if ($activeIds === []) {
            return [];
        }

        $translationsByProductId = [];
        foreach (
            $this->database->table('product_translation')
                ->where('product_id', $activeIds)
                ->where('locale', $locale->getCode())
                ->fetchAll() as $row
        ) {
            $translationLocale = $this->string($row->locale, 'product_translation.locale');
            $relatedId = $this->integer($row->product_id, 'product_translation.product_id');
            $translationsByProductId[$relatedId] = new ProductTranslationDto(
                $this->integer($row->id, 'product_translation.id'),
                $relatedId,
                Locale::fromString($translationLocale),
                $this->string($row->name, 'product_translation.name'),
                $this->string($row->slug, 'product_translation.slug'),
                $this->nullableString($row->short_description, 'product_translation.short_description'),
                $this->nullableString($row->description, 'product_translation.description'),
                $this->nullableString($row->meta_title, 'product_translation.meta_title'),
                $this->nullableString($row->meta_description, 'product_translation.meta_description'),
            );
        }

        $result = [];
        foreach ($relations as $relation) {
            $relatedId = $relation['productId'];
            if (!isset($productsById[$relatedId])) {
                continue;
            }

            $result[] = new ProductRelationDto(
                $relatedId,
                $productsById[$relatedId],
                $relation['type'],
                $relation['sortOrder'],
                $translationsByProductId[$relatedId] ?? null,
            );
        }

        return $result;
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\\A-?[0-9]+\\z/D', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer !== false) {
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

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }
}
