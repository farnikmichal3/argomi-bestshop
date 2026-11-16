<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Application\Dto\MessageCatalog;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;

/**
 * Načítá systémové texty jedné vlastnické domény a jednoho jazyka.
 */
interface MessageStore
{
    /**
     * Načte všechny dostupné texty pro zadanou doménu a jazyk.
     *
     * Chybějící klíče zůstávají v katalogu nepřítomné; fallback řeší
     * až překladač v navazujícím kroku.
     */
    public function load(MessageDomain $domain, Locale $locale): MessageCatalog;
}
