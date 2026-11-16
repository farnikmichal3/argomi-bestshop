<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;

/**
 * Zneplatňuje uložené systémové katalogy po změně překladových dat.
 */
interface MessageCacheInvalidator
{
    /**
     * Bez filtrů zneplatní všechny katalogy; jinak pouze průnik zadaných filtrů.
     */
    public function invalidate(?MessageDomain $domain = null, ?Locale $locale = null): void;
}
