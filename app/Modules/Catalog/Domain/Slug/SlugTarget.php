<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Slug;

/**
 * Typ katalogového překladu, jehož slug má vlastní unikátní prostor.
 *
 * Produktové a kategoriové URL se mohou shodovat; unikátnost se vynucuje
 * samostatně v každé překladové tabulce a vždy v rámci konkrétního jazyka.
 */
enum SlugTarget: string
{
    case Product = 'product';
    case Category = 'category';
}
