<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;

/**
 * Zaznamená chybějící text v požadovaném jazyce, i když se použije fallback.
 */
interface MissingTranslationReporter
{
    public function report(MessageDomain $domain, Locale $locale, string $messageKey): void;
}
