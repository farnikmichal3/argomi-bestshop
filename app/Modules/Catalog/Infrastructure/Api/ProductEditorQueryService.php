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
            $product,
            $this->products->findTranslations($productId),
            $categoryIds,
        );
    }

    public function findTaxClasses(): array
    {
        $options = [];
        foreach ($this->database->table('tax_class')->order('code ASC')->fetchAll() as $row) {
            if (!is_string($row->code) || !is_string($row->name)) {
                throw new \UnexpectedValueException('Daňová třída obsahuje neplatný kód nebo název.');
            }
            $id = $row->id;
            if (is_string($id) && ctype_digit($id)) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
            }
            if (!is_int($id)) {
                throw new \UnexpectedValueException('Daňová třída obsahuje neplatné ID.');
            }
            $options[] = ['id' => $id, 'code' => $row->code, 'name' => $row->name];
        }
        return $options;
    }
}
