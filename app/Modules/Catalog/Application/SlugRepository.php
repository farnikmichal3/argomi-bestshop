<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Domain\Locale\Locale;

/**
 * Kontrola dostupnosti slugů v překladech produktů a kategorií.
 */
interface SlugRepository
{
    /**
     * Ověří dostupnost slug v rámci cílového typu a jazyka.
     *
     * Pokud je předáno ID entity, její vlastní překlad se při kontrole ignoruje.
     */
    public function isAvailable(
        SlugTarget $target,
        Locale $locale,
        string $slug,
        ?int $excludeEntityId = null,
    ): bool;
}
