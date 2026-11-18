<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

interface VariantSelectionRepository
{
    /**
     * Vrátí typy os aktivního produktu; null znamená neexistující/neaktivní produkt.
     *
     * @return array<string, string>|null Mapa kód atributu => typ atributu.
     */
    public function findActiveAxisTypes(int $productId): ?array;

    /**
     * Najde aktivní variantu produktu podle kanonického SHA-256 hashe.
     */
    public function findActiveVariantId(int $productId, string $combinationHash): ?int;
}
