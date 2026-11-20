<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Modules\Translation\Api\Dto\MessageTranslation;
use App\Shared\Domain\Locale\Locale;

/**
 * Read-only vyhledávání systémových překladů pro administrační přehled.
 */
interface MessageTranslationReader
{
    /**
     * @return list<MessageTranslation>
     */
    public function search(
        ?TranslationDomain $domain = null,
        ?Locale $locale = null,
        ?TranslationMessageStatus $status = null,
    ): array;
}
