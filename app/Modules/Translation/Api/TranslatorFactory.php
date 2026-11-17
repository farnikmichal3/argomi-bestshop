<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Shared\Domain\Locale\Locale;
use Nette\Localization\Translator;

/**
 * Veřejný kontrakt pro vytvoření překladače konkrétní domény a locale.
 *
 * Volající získává pouze standardní Nette Translator; způsob načítání,
 * cache i databázový fallback zůstávají interní implementací modulu.
 */
interface TranslatorFactory
{
    public function create(TranslationDomain $domain, Locale $locale): Translator;
}
