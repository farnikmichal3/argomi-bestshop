<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application\Dto;

use App\Modules\Translation\Domain\MessageDomain;

/**
 * Jeden chybějící systémový překlad zaznamenaný během požadavku.
 */
final readonly class MissingTranslation
{
    public function __construct(
        public MessageDomain $domain,
        public string $locale,
        public string $messageKey,
    ) {}
}
