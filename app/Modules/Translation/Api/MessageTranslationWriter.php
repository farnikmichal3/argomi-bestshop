<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Shared\Domain\Locale\Locale;

/**
 * Zapisuje pouze vlastní systémové texty; systémové zdroje zůstávají chráněné.
 */
interface MessageTranslationWriter
{
    public function createCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
        string $messageText,
    ): void;

    public function updateCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
        string $messageText,
    ): void;

    public function deleteCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
    ): void;
}
