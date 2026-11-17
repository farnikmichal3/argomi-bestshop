<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Translation;

use RuntimeException;

/**
 * Signalizuje chybu při formátování systémové zprávy pomocí ICU.
 */
final class MessageFormattingException extends RuntimeException
{
    public function __construct(
        string $messageKey,
        string $locale,
        string $reason,
    ) {
        parent::__construct(sprintf(
            'Unable to format translation "%s" for locale "%s": %s',
            $messageKey,
            $locale,
            $reason,
        ));
    }
}
