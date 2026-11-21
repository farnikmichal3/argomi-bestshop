<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

/**
 * Resolves catalog access from authenticated role names.
 * A future Identity/RBAC provider can supply roles without changing this policy.
 */
final class CatalogPermissionResolver
{
    private const VIEW_ROLES = [
        'catalog_viewer',
        'catalog_manager',
    ];

    private const MANAGE_ROLES = [
        'catalog_manager',
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
