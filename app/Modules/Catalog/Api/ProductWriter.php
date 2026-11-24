<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Veřejný kontrakt pro vytvoření a úpravu základních údajů produktu.
 *
 * Překlady jsou indexovány kódem aktivního jazyka. Jejich obsah validuje
 * interní aplikační služba Catalogu.
 */
interface ProductWriter
{
    /**
     * @param array<string, mixed> $translations
     * @param list<int> $categoryIds
     */
    public function create(
        string $code,
        ProductAdminStatus $status,
        ProductAdminType $type,
        int $taxClassId,
        array $translations,
        array $categoryIds,
    ): int;

    /**
     * @param array<string, mixed> $translations
     * @param list<int> $categoryIds
     */
    public function update(
        int $productId,
        string $code,
        ProductAdminStatus $status,
        ProductAdminType $type,
        int $taxClassId,
        array $translations,
        array $categoryIds,
    ): void;
}
