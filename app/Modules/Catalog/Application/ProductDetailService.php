<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductDetailDto;
use App\Shared\Domain\Locale\Locale;

/**
 * Aplikační vstupní bod pro načtení katalogového detailu produktu.
 */
final readonly class ProductDetailService
{
    public function __construct(
        private ProductDetailRepository $repository,
    ) {}

    /**
     * Načte detail aktivního produktu v explicitně požadovaném jazyce.
     *
     * @throws \InvalidArgumentException Při neplatném ID produktu.
     */
    public function getById(int $productId, Locale $locale): ?ProductDetailDto
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('ID produktu musí být kladné.');
        }

        return $this->repository->findActiveDetail($productId, $locale);
    }
}
