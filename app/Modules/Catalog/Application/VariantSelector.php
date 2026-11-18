<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
use InvalidArgumentException;

/**
 * Vybere aktivní variantu podle úplné a typově správné sady hodnot os.
 */
final readonly class VariantSelector
{
    public function __construct(
        private VariantSelectionRepository $repository,
    ) {}

    /**
     * @param array<string, array{type: string, value: mixed}> $attributes
     *
     * @throws InvalidArgumentException Při neúplném, nadbytečném nebo chybně typovaném výběru.
     */
    public function select(int $productId, array $attributes): ?int
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }

        $axisTypes = $this->repository->findActiveAxisTypes($productId);

        if ($axisTypes === null) {
            return null;
        }

        if ($axisTypes === []) {
            throw new InvalidArgumentException('Produkt nemá definované žádné variantní osy.');
        }

        $selectedCodes = array_keys($attributes);
        $axisCodes = array_keys($axisTypes);
        sort($selectedCodes, SORT_STRING);
        sort($axisCodes, SORT_STRING);

        if ($selectedCodes !== $axisCodes) {
            throw new InvalidArgumentException('Výběr musí obsahovat právě všechny variantní osy produktu.');
        }

        foreach ($attributes as $code => $attribute) {
            if (!is_array($attribute) || !isset($attribute['type']) || $attribute['type'] !== $axisTypes[$code]) {
                throw new InvalidArgumentException(sprintf('Výběr osy %s neodpovídá typu definovanému produktem.', $code));
            }
        }

        return $this->repository->findActiveVariantId(
            $productId,
            VariantCombinationHash::create($attributes),
        );
    }
}
