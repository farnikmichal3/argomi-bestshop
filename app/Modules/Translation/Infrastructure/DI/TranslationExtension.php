<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\DI;

use App\Modules\Translation\Infrastructure\Cache\CachedMessageStore;
use App\Modules\Translation\Infrastructure\Cache\NetteMessageCacheInvalidator;
use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageStore;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use Nette\Caching\Cache;
use Nette\DI\CompilerExtension;

/**
 * DI extension modulu Translation.
 *
 * Modul zpřístupňuje cacheovaný katalog a samostatný invalidátor.
 * Databázové úložiště zůstává interním detailem dekorátoru.
 */
final class TranslationExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('cache'))
            ->setFactory(Cache::class, ['@cache.storage', 'translation']);

        $builder->addDefinition($this->prefix('databaseMessageStore'))
            ->setType(DatabaseMessageStore::class)
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('messageStore'))
            ->setType(CachedMessageStore::class)
            ->setArguments([
                '@' . $this->prefix('databaseMessageStore'),
                '@' . $this->prefix('cache'),
            ]);

        $builder->addDefinition($this->prefix('messageCacheInvalidator'))
            ->setType(NetteMessageCacheInvalidator::class)
            ->setArguments([
                '@' . $this->prefix('cache'),
            ]);

        $builder->addDefinition($this->prefix('translatorFactory'))
            ->setType(DatabaseTranslatorFactory::class)
            ->setArguments([
                '@' . $this->prefix('messageStore'),
            ]);
    }
}
