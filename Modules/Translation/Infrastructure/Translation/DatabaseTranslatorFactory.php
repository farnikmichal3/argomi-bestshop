<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Translation;

use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Application\MissingTranslationReporter;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;

/**
 * Databázová implementace veřejné továrny překladačů.
 *
 * Veřejnou doménu mapuje na interní model modulu; načítání přes MessageStore
 * zachovává persistentní cache i fallback stejné vlastnické domény.
 */
final readonly class DatabaseTranslatorFactory implements TranslatorFactory
{
    public function __construct(
        private MessageStore $messageStore,
        private LocaleProvider $localeProvider,
        private MissingTranslationReporter $missingTranslationReporter,
    ) {}

    public function createTranslator(TranslationDomain $domain, Locale $locale): DatabaseTranslator
    {
        $messageDomain = match ($domain) {
            TranslationDomain::Catalog => MessageDomain::Catalog,
            TranslationDomain::Cart => MessageDomain::Cart,
            TranslationDomain::Checkout => MessageDomain::Checkout,
            TranslationDomain::Admin => MessageDomain::Admin,
        };

        $catalog = $this->messageStore->load($messageDomain, $locale);
        $defaultLocale = $this->localeProvider->getDefault();

        if ($locale->equals($defaultLocale)) {
            return new DatabaseTranslator($catalog, null, $this->missingTranslationReporter);
        }

        return new DatabaseTranslator(
            $catalog,
            $this->messageStore->load($messageDomain, $defaultLocale),
            $this->missingTranslationReporter,
        );
    }
}
