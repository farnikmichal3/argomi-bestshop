<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/** Veřejně dostupné typy produktu pro katalogové administrační API. */
enum ProductAdminType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Service = 'service';
}
