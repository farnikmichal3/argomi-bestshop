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
        } else {
            if ($domain !== null) {
                $tags[] = 'translation-domain-' . $domain->value;
            }

            if ($locale !== null) {
                $tags[] = 'translation-locale-' . $locale->getCode();
            }
        }

        $this->cache->clean([
            Cache::Tags => $tags,
        ]);
    }
}
