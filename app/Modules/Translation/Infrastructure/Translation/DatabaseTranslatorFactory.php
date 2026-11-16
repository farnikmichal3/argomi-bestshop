<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Translation;

use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;

/**
 * Vytváří překladač s explicitní vlastnickou doménou a locale.
 *
 * Načítání probíhá přes MessageStore, takže se automaticky využívá
 * persistentní cache katalogů a nevzniká skrytý globální jazykový kontext.
 */
final readonly class DatabaseTranslatorFactory
{
    public function __construct(
        private MessageStore $messageStore,
    ) {}

    public function create(MessageDomain $domain, Locale $locale): DatabaseTranslator
    {
        return new DatabaseTranslator(
            $this->messageStore->load($domain, $locale),
        );
    }
}
