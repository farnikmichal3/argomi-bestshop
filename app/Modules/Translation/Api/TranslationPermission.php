<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

/**
 * Server-side oprávnění pro čtení a správu systémových překladů.
 */
interface TranslationPermission
{
    public function canView(): bool;

    public function canManage(): bool;
}
