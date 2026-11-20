<?php

declare(strict_types=1);

namespace App\Modules\Translation\Domain;

/**
 * Vlastnická doména systémového textu.
 *
 * Hodnota určuje pouze povolenou oblast úložiště; název databázové tabulky
 * mapuje výhradně infrastrukturní adaptér.
 */
enum MessageDomain: string
{
    case Catalog = 'catalog';
    case Cart = 'cart';
    case Checkout = 'checkout';
    case Admin = 'admin';
}
