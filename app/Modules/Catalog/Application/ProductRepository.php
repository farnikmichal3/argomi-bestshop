<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProductDto;
use App\Modules\Catalog\Application\Dto\ProductTranslationDto;
use App\Modules\Catalog\Application\Dto\ProductListingDto;
use App\Shared\Domain\Locale\Locale;

/**
 * Aplikační kontrakt pro čtení katalogových produktů a jejich překladů.
 *
 * Rozhraní neodhaluje Nette Database ani ActiveRow. Konkrétní způsob
 * načítání a mapování databázových záznamů zůstává v infrastruktuře modulu.
 */
interface ProductRepository
{
    /**
     * Najde produkt podle interního primárního klíče.
     *
     * @return ProductDto|null Produkt, nebo null, pokud neexistuje.
     */
    public function findById(int $id): ?ProductDto;

    /**
     * Najde produkt podle jeho stabilního katalogového kódu.
     *
     * @return ProductDto|null Produkt, nebo null, pokud kód neexistuje.
     */
    public function findByCode(string $code): ?ProductDto;

    /**
     * Najde překlad produktu pro konkrétní jazyk.
     *
     * @return ProductTranslationDto|null Překlad, nebo null, pokud není uložen.
     */
    public function findTranslation(int $productId, Locale $locale): ?ProductTranslationDto;

    /**
     * Vrátí všechny uložené překlady produktu v deterministickém pořadí.
     *
     * @return list<ProductTranslationDto>
     */
    public function findTranslations(int $productId): array;

    /**
     * Vrátí aktivní produkty přímo zařazené do kategorie.
     *
     * Stránkování začíná od 1. Počet položek na stránku je omezen na 100.
     * Výsledek obsahuje překlad pro požadovaný jazyk, pokud existuje.
     *
     * @throws \\InvalidArgumentException Při neplatném ID nebo stránkování.
     */
    public function findByCategory(
        int $categoryId,
        Locale $locale,
        int $page = 1,
        int $perPage = 12,
        ProductSortOrder $sort = ProductSortOrder::CategoryPosition,
    ): ProductListingDto;
}
