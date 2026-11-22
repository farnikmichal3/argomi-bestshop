<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

/**
 * Vyhodnocuje stabilní role Translation nezávisle na autentizačním adaptéru.
 */
final class TranslationPermissionResolver
{
    private const VIEW_ROLES = [
        'admin',
        'translation_viewer',
        'translation_manager',
    ];

    private const MANAGE_ROLES = [
        'admin',
        'translation_manager',
    ];

    /**
     * @param list<string> $roles
     */
    public function canView(bool $authenticated, array $roles): bool
    {
        return $authenticated && array_intersect(self::VIEW_ROLES, $roles) !== [];
    }

    /**
     * @param list<string> $roles
     */
    public function canManage(bool $authenticated, array $roles): bool
    {
        return $authenticated && array_intersect(self::MANAGE_ROLES, $roles) !== [];
    }
}
