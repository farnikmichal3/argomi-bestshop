<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

/**
 * Povolené stabilní způsoby řazení produktů v kategorii.
 *
 * Hodnoty jsou aplikační volby, nikdy se nepředávají jako SQL identifikátory.
 */
enum ProductSortOrder
{
    case CategoryPosition;
    case ProductCodeAscending;
    case ProductCodeDescending;
    case Newest;
    case Oldest;
}
