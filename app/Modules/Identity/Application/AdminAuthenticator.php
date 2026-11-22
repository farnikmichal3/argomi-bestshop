<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

/**
 * Authenticates administrator accounts against stored password hashes.
 */
final class AdminAuthenticator implements Authenticator
{
    private const INVALID_CREDENTIALS_MESSAGE = 'Invalid administrator credentials.';

    // Valid bcrypt hash used for unknown/disabled accounts to reduce timing differences.
    private const DUMMY_PASSWORD_HASH = '$2y$10$5F4m1w2m8Qz0v6a9s1p4eO7dX3uLkG8bH2cR9tV6aP0qW5yZ1nM3K';

    public function __construct(
        private readonly AdminAuthenticationRepository $repository,
    ) {}

    public function authenticate(string $username, string $password): IIdentity
    {
        $email = mb_strtolower(trim($username));
        $account = $this->repository->findByEmail($email);

        if ($account === null || $account->status !== 'active') {
            password_verify($password, self::DUMMY_PASSWORD_HASH);
            throw new AuthenticationException(self::INVALID_CREDENTIALS_MESSAGE, Authenticator::IdentityNotFound);
        }

        if (!password_verify($password, $account->passwordHash)) {
            throw new AuthenticationException(self::INVALID_CREDENTIALS_MESSAGE, Authenticator::InvalidCredential);
        }

        $this->repository->recordSuccessfulLogin($account->id);

        return new SimpleIdentity(
            $account->id,
            $account->roles,
            ['email' => $account->email],
        );
    }
}
