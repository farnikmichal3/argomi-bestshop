<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use RuntimeException;

/**
 * Thrown when a catalog write operation is attempted without management access.
 */
final class CatalogAccessDeniedException extends RuntimeException
{
}
