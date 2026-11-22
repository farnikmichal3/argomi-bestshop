<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/** Veřejně dostupné stavy produktu pro katalogové administrační API. */
enum ProductAdminStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
