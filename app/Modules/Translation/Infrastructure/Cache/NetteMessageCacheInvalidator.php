<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Cache;

use App\Modules\Translation\Application\MessageCacheInvalidator;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Nette\Caching\Cache;

/**
 * Invaliduje katalogy podle stabilních tagů sdílených s CachedMessageStore.
 */
final class NetteMessageCacheInvalidator implements MessageCacheInvalidator
{
    public function __construct(
        private readonly Cache $cache,
    ) {}

    public function invalidate(?MessageDomain $domain = null, ?Locale $locale = null): void
    {
        $tags = [];

        if ($domain === null && $locale === null) {
            $tags[] = 'translation-catalogs';
        } elseif ($domain !== null && $locale !== null) {
            // Kombinovaný tag zajišťuje průnik filtrů, nikoliv jejich sjednocení.
            $tags[] = 'translation-domain-locale-' . $domain->value . '-' . $locale->getCode();
        } elseif ($domain !== null) {
            $tags[] = 'translation-domain-' . $domain->value;
        } else {
            $tags[] = 'translation-locale-' . $locale->getCode();
        }

        $this->cache->clean([
            Cache::Tags => $tags,
        ]);
    }
}
