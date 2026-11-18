<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Security;

use App\Modules\Translation\Api\TranslationPermission;
use App\Modules\Translation\Application\TranslationPermissionResolver;
use Nette\Security\User;

/**
 * Adaptuje role přihlášeného uživatele na oprávnění modulu Translation.
 */
final class NetteTranslationPermissionService implements TranslationPermission
{
    public function __construct(
        private readonly User $user,
        private readonly TranslationPermissionResolver $resolver,
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
