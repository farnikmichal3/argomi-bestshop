<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain;

/**
 * Povolené druhy směrových vztahů mezi katalogovými produkty.
 */
enum ProductRelationType: string
{
    case Accessory = 'accessory';
    case Alternative = 'alternative';
    case Compatible = 'compatible';
}
