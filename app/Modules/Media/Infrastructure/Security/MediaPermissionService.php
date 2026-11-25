<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Security;

use App\Modules\Media\Api\MediaPermission;
use Nette\Security\User;

final readonly class MediaPermissionService implements MediaPermission
{
    private const VIEW_ROLES = ['admin', 'catalog_viewer', 'catalog_manager'];
    private const MANAGE_ROLES = ['admin', 'catalog_manager'];

    public function __construct(private User $user) {}

    public function canView(): bool
    {
        return $this->user->isLoggedIn() && array_intersect(self::VIEW_ROLES, $this->user->getRoles()) !== [];
    }

    public function canManage(): bool
    {
        return $this->user->isLoggedIn() && array_intersect(self::MANAGE_ROLES, $this->user->getRoles()) !== [];
    }
}
