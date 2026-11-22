<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use RuntimeException;

/**
 * Kategorie nelze odstranit, protože není prázdná nebo již neexistuje.
 */
final class CategoryDeletionBlockedException extends RuntimeException
{
    public const NOT_FOUND = 'not_found';
    public const HAS_CHILDREN = 'has_children';
    public const HAS_PRODUCTS = 'has_products';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
