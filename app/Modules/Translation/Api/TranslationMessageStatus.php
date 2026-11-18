<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

/**
 * Filtr provozního stavu systémového překladu.
 */
enum TranslationMessageStatus: string
{
    case Core = 'core';
    case Custom = 'custom';
    case Outdated = 'outdated';
}
