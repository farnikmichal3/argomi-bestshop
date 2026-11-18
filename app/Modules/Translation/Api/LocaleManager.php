<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use App\Modules\Translation\Api\Dto\ManagedLocale;
use App\Shared\Domain\Locale\Locale;

/**
 * Správa jazyků obchodu; aktivní jazyk je dostupný veřejnému LocaleProvideru.
 */
interface LocaleManager
{
    /** @return list<ManagedLocale> */
    public function getAll(): array;

    public function add(Locale $locale, string $name, int $sortOrder = 0): void;

    public function setActive(Locale $locale, bool $active): void;
}
