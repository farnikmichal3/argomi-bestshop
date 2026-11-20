<?php

declare(strict_types=1);

namespace App\Core\Environment;

final class DebugModePolicy
{
    /**
     * SQL diagnostika a detailní chybové výpisy smějí běžet jen v explicitním vývoji.
     *
     * Neznámé, chybějící ani jinak pojmenované prostředí se z bezpečnostních důvodů
     * považuje za produkční režim.
     */
    public function isEnabled(mixed $environment): bool
    {
        return $environment === 'development';
    }
}
