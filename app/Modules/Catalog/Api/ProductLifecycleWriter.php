<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Api;

/** Veřejné operace životního cyklu produktu v administraci Catalog. */
interface ProductLifecycleWriter
{
    /** Nevratně archivuje produkt a databáze deaktivuje jeho aktivní varianty. */
    public function archive(int $productId): void;

    /** Vytvoří nový koncept z archivovaného produktu bez historických a unikátních dat. */
    public function createCopyFromArchived(int $productId): int;
}
