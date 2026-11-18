<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api\Dto;

use App\Modules\Translation\Api\TranslationDomain;

/**
 * Jediný řádek systémového překladu určený pro bezpečné zobrazení v administraci.
 */
final readonly class MessageTranslation
{
    public function __construct(
        public TranslationDomain $domain,
        public string $messageKey,
        public string $locale,
        public string $messageText,
        public string $source,
        public bool $isCustomized,
        public bool $isOutdated,
    ) {}
}
