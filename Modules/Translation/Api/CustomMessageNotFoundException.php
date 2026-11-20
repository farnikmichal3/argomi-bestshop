<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use RuntimeException;

/**
 * Vlastní překlad neexistuje nebo je chráněným systémovým zdrojem.
 */
final class CustomMessageNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Vlastní překlad nebyl nalezen.');
    }
}
