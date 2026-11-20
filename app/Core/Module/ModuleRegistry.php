<?php

declare(strict_types=1);

namespace App\Core\Module;

final class ModuleRegistry
{
    /** @var list<string> */
    public const MODULES = [
        'Cart',
        'Catalog',
        'Checkout',
        'Customer',
        'Identity',
        'Inventory',
        'Mailing',
        'Orders',
        'Payments',
        'Pricing',
        'Settings',
        'Shipping',
        'Tax',
        'Translation',
    ];
}
