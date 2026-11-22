<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Product;

/** Obchodní a technická povaha produktu; nezaměňovat se stavem ani konfigurátorem. */
enum ProductType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Service = 'service';
}
