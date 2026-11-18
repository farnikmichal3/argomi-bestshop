<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Option;

/** Typ volby zákazníka podle katalogového databázového modelu. */
enum CustomerOptionType: string
{
    case Choice = 'choice';
    case Text = 'text';
}
