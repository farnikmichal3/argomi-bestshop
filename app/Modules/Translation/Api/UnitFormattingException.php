<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use RuntimeException;
use Throwable;

/**
 * Označuje chybný ICU vzor nebo selhání ICU při formátování názvu jednotky.
 */
final class UnitFormattingException extends RuntimeException
{
    public function __construct(
        string $locale,
        string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Unable to format a unit name for locale "%s": %s', $locale, $reason),
            0,
            $previous,
        );
    }
}
