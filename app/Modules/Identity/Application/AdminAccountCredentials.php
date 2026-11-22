<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

/**
 * Credentials and role names required for administrator authentication.
 *
 * The password hash is intentionally kept out of the resulting Nette identity.
 */
final readonly class AdminAccountCredentials
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $passwordHash,
        public string $status,
        public array $roles,
    ) {}
}
