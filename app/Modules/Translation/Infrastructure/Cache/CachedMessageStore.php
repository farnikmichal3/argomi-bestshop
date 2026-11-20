<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Cache;

use App\Modules\Translation\Application\Dto\MessageCatalog;
use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Nette\Caching\Cache;

/**
 * Dekoruje databázové úložiště katalogovou cache s explicitní invalidací.
 */
final class CachedMessageStore implements MessageStore
{
    public function __construct(
        private readonly MessageStore $inner,
        private readonly Cache $cache,
    ) {}

    public function load(MessageDomain $domain, Locale $locale): MessageCatalog
    {
        $key = $domain->value . ':' . $locale->getCode();

        $catalog = $this->cache->load($key);

        if ($catalog instanceof MessageCatalog) {
            return $catalog;
        }

        $catalog = $this->inner->load($domain, $locale);

        $this->cache->save($key, $catalog, [
            Cache::Tags => [
                'translation-catalogs',
                'translation-domain-' . $domain->value,
                'translation-locale-' . $locale->getCode(),
                'translation-domain-locale-' . $domain->value . '-' . $locale->getCode(),
            ],
        ]);

        return $catalog;
    }
}
