<?php

declare(strict_types=1);

namespace App\\Modules\\Translation\\Api;

/**
 * Veřejné označení vlastníka systémových překladů.
 *
 * Modul Translation interně mapuje tyto stabilní hodnoty na své úložiště;
 * ostatní vrstvy nemusí znát jeho doménový model ani databázové tabulky.
 */
enum TranslationDomain: string
{
    case Catalog = 'catalog';
    case Cart = 'cart';
    case Checkout = 'checkout';
}
