<?php

declare(strict_types=1);

namespace App\Shared\Domain\Locale;

use DomainException;

/**
 * Označuje chybějící výchozí aktivní jazyk v konfiguraci obchodu.
 */
final class DefaultLocaleNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('V databázi není nastaven žádný výchozí aktivní jazyk.');
    }
}
