<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductRelationOrderDto;
use App\Modules\Catalog\Domain\ProductRelationType;
use App\Shared\Application\Transaction\Transaction;

/**
 * Aplikační use-cases pro správu směrových vztahů katalogových produktů.
 */
final class ProductRelationService
{
    public function __construct(
        private readonly ProductRelationRepository $relations,
        private readonly Transaction $transaction,
    ) {}

    public function add(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
        int $sortOrder = 0,
    ): void {
        $this->validateProductIds($productId, $relatedProductId);

        if ($sortOrder < 0 || $sortOrder > 2_147_483_647) {
            throw new \InvalidArgumentException('Pořadí vztahu musí být nezáporné celé číslo v rozsahu SQL INT.');
        }

        $this->transaction->run(function () use ($productId, $relatedProductId, $type, $sortOrder): void {
            $this->relations->add($productId, $relatedProductId, $type, $sortOrder);
        });
    }

    public function remove(
        int $productId,
        int $relatedProductId,
        ProductRelationType $type,
    ): bool {
        $this->validateProductIds($productId, $relatedProductId);

        return $this->transaction->run(
            fn(): bool => $this->relations->remove($productId, $relatedProductId, $type),
        );
    }

    /**
     * Přijímá úplný seznam vztahů produktu, nikoli pouze jejich podmnožinu.
     *
     * @param list<ProductRelationOrderDto> $orderedRelations
     */
    public function reorder(int $productId, array $orderedRelations): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('ID produktu musí být kladné.');
        }

        $seen = [];
        foreach ($orderedRelations as $relation) {
            if ($relation->relatedProductId < 1) {
                throw new \InvalidArgumentException('Pořadí obsahuje neplatnou položku vztahu.');
            }

            $key = $relation->relatedProductId . ':' . $relation->type->value;
            if (isset($seen[$key])) {
                throw new \InvalidArgumentException('Pořadí obsahuje duplicitní vztah.');
            }

            $seen[$key] = true;
        }

        $this->transaction->run(function () use ($productId, $orderedRelations): void {
            $this->relations->reorder($productId, $orderedRelations);
        });
    }

    private function validateProductIds(int $productId, int $relatedProductId): void
    {
        if ($productId < 1 || $relatedProductId < 1) {
            throw new \InvalidArgumentException('ID obou produktů musí být kladná.');
        }

        if ($productId === $relatedProductId) {
            throw new \InvalidArgumentException('Produkt nemůže být ve vztahu sám se sebou.');
        }
    }
}
