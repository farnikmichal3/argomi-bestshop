<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Product;

/** Stav životního cyklu produktu podle katalogového databázového modelu. */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
