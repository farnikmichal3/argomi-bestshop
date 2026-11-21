<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Security;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\CatalogPermissionResolver;
use Nette\Security\User;

/**
 * Adapts Nette's current identity roles to Catalog permission checks.
 */
final class NetteCatalogPermissionService implements CatalogPermission
{
    public function __construct(
        private readonly User $user,
        private readonly CatalogPermissionResolver $resolver,
    ) {}

    public function canView(): bool
    {
        return $this->resolver->canView($this->user->isLoggedIn(), $this->user->getRoles());
    }

    public function canManage(): bool
    {
        return $this->resolver->canManage($this->user->isLoggedIn(), $this->user->getRoles());
    }
}
