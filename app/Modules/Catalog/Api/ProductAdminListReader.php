<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Api;

use App\\Modules\\Catalog\\Api\\Dto\\ProductAdminListFilter;
use App\\Modules\\Catalog\\Api\\Dto\\ProductAdminListing;
use App\\Shared\\Domain\\Locale\\Locale;

/**
 * Veřejný čtecí kontrakt administrátorského katalogového výpisu.
 *
 * Výsledek zahrnuje produkty všech stavů. Oprávnění se kontroluje
 * v administrační prezentační vrstvě přes CatalogPermission.
 */
interface ProductAdminListReader
{
    public function findProducts(
        ProductAdminListFilter $filter,
        Locale $locale,
    ): ProductAdminListing;
}
