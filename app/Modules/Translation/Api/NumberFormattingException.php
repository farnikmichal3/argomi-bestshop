<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use RuntimeException;
use Throwable;

/**
 * Označuje selhání ICU při formátování čísla nebo peněžní částky.
 */
final class NumberFormattingException extends RuntimeException
{
    public function __construct(
        string $locale,
        string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Unable to format a value for locale "%s": %s', $locale, $reason),
            0,
            $previous,
        );
    }
}
