<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\Dto\ProductEditorData;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Application\ProductRepository;
use Nette\Database\Explorer;

final readonly class ProductEditorQueryService implements ProductEditorReader
{
    public function __construct(
        private ProductRepository $products,
        private Explorer $database,
    ) {}

    public function findForEdit(int $productId): ?ProductEditorData
    {
        if ($productId < 1) {
            return null;
        }

        $product = $this->products->findById($productId);
        if ($product === null) {
            return null;
        }

        $translations = [];
        foreach ($this->products->findTranslations($productId) as $translation) {
            $translations[$translation->locale->getCode()] = [
                'name' => $translation->name,
                'shortDescription' => $translation->shortDescription,
                'description' => $translation->description,
                'metaTitle' => $translation->metaTitle,
                'metaDescription' => $translation->metaDescription,
            ];
        }

        $categoryIds = [];
        foreach ($this->database->table('product_category')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll() as $row) {
            $value = $row->category_id;
            if (is_int($value)) {
                $categoryIds[] = $value;
            } elseif (is_string($value) && ctype_digit($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                $categoryIds[] = (int) $value;
            } else {
                throw new \UnexpectedValueException('Databázové ID přiřazené kategorie není platné celé číslo.');
            }
        }

        return new ProductEditorData(
            $product->id,
            $product->code,
            $product->status,
            $product->type->value,
            $product->taxClassId,
            $translations,
            $categoryIds,
        );
    }

}
