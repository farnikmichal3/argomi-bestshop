<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductRelationDto;
use App\Modules\Catalog\Application\Dto\ProductRelationOrderDto;
use App\Modules\Catalog\Application\Dto\ProductRelationOptionDto;
use App\Modules\Catalog\Application\Dto\ProductTranslationDto;
use App\Modules\Catalog\Application\ProductRelationRepository;
use App\Modules\Catalog\Domain\ProductRelationType;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Dávkové načítání a transakční persistence směrových vztahů produktů.
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

    public function findAvailableProducts(int $productId, Locale $locale): array
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('ID produktu musí být kladné.');
        }

        $products = $this->database->table('product')
            ->where('status', 'active')
            ->where('id != ?', $productId)
            ->order('code ASC, id ASC')
            ->fetchAll();

        if ($products === []) {
            return [];
        }

        $ids = [];
        $productsById = [];
        foreach ($products as $row) {
            $id = $this->integer($row->id, 'product.id');
            $ids[] = $id;
            $productsById[$id] = $this->string($row->code, 'product.code');
        }

        $namesById = [];
        foreach ($this->database->table('product_translation')
            ->where('product_id', $ids)
            ->where('locale', $locale->getCode())
            ->fetchAll() as $row) {
            $namesById[$this->integer($row->product_id, 'product_translation.product_id')]
                = $this->string($row->name, 'product_translation.name');
        }

        $result = [];
        foreach ($productsById as $id => $code) {
            $result[] = new ProductRelationOptionDto($id, $code, $namesById[$id] ?? null);
        }

        return $result;
    }

    public function add(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
        int $sortOrder,
    ): void {
        $this->assertActiveProduct($productId);
        $this->assertActiveProduct($relatedProductId);

        $this->database->table('product_relation')->insert([
            'product_id' => $productId,
            'related_product_id' => $relatedProductId,
            'type' => $type->value,
            'sort_order' => $sortOrder,
        ]);
    }

    public function remove(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
    ): bool {
        $this->assertActiveProduct($productId);

        return $this->database->table('product_relation')
            ->where('product_id', $productId)
            ->where('related_product_id', $relatedProductId)
            ->where('type', $type->value)
            ->delete() > 0;
    }

    public function reorder(int $productId, array $orderedRelations): void
    {
        $this->assertActiveProduct($productId);

        $rows = $this->database->table('product_relation')
            ->where('product_id', $productId)
            ->fetchAll();

        $rowsByKey = [];
        foreach ($rows as $row) {
            $key = $this->relationKey(
                $this->integer($row->related_product_id, 'product_relation.related_product_id'),
                ProductRelationType::from($this->string($row->type, 'product_relation.type')),
            );
            $rowsByKey[$key] = $row;
        }

        if (count($rowsByKey) !== count($orderedRelations)) {
            throw new \DomainException('Nové pořadí musí obsahovat všechny existující vztahy právě jednou.');
        }

        foreach ($orderedRelations as $position => $relation) {
            $row = $rowsByKey[$this->relationKey($relation->relatedProductId, $relation->type)] ?? null;
            if (!$row instanceof ActiveRow) {
                throw new \DomainException('Nové pořadí obsahuje neexistující vztah produktu.');
            }

            $row->update(['sort_order' => $position]);
        }
    }

    private function assertActiveProduct(int $productId): void
    {
        $product = $this->database->table('product')
            ->where('id', $productId)
            ->where('status', 'active')
            ->fetch();

        if (!$product instanceof ActiveRow) {
            throw new \DomainException(sprintf('Aktivní produkt s ID %d neexistuje.', $productId));
        }
    }

    private function relationKey(int $relatedProductId, ProductRelationType $type): string
    {
        return $relatedProductId . ':' . $type->value;
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
