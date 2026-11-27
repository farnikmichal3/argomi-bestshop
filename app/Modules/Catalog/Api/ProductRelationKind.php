<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Veřejné typy směrových vztahů mezi produkty.
 */
enum ProductRelationKind: string
{
    case Accessory = 'accessory';
    case Alternative = 'alternative';
    case Compatible = 'compatible';
}
