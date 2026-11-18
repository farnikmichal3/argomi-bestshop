<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

use App\\Modules\\Catalog\\Application\\Dto\\CategoryTreeDto;
use App\\Shared\\Domain\\Locale\\Locale;

/**
 * Aplikační kontrakt pro čtení hierarchie kategorií katalogu.
 *
 * Výsledky jsou stromem typovaných DTO; databázové řádky ani ActiveRow
 * nepřekračují hranici persistence vrstvy.
 */
interface CategoryRepository
{
    /**
     * Vrátí úplný strom kategorií včetně neaktivních uzlů.
     *
     * Sourozenci i kořenové kategorie jsou řazeni podle sort_order a následně ID.
     *
     * @return list<CategoryTreeDto>
     */
    public function findTree(Locale $locale): array;

    /**
     * Vrátí strom pouze aktivních kategorií.
     *
     * Neaktivní uzel vyřadí i jeho potomky, aby ve výsledku nevznikly osiřelé
     * kategorie bez viditelného rodiče.
     *
     * @return list<CategoryTreeDto>
     */
    public function findActiveTree(Locale $locale): array;
}
