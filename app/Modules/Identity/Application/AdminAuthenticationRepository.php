<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use Nette\Database\Explorer;

/**
 * Persistence access for administrator authentication.
 */
final class AdminAuthenticationRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findByEmail(string $email): ?AdminAccountCredentials
    {
        $row = $this->database->table('admin_user')
            ->where('email', $email)
            ->fetch();

        if ($row === null) {
            return null;
        }

        $roleRows = $this->database->query(
            'SELECT r.code
             FROM admin_role r
             INNER JOIN admin_user_role ur ON ur.admin_role_id = r.id
             WHERE ur.admin_user_id = ?
             ORDER BY r.code',
            $row->id,
        )->fetchAll();

        $roles = [];
        foreach ($roleRows as $roleRow) {
            $code = $roleRow['code'] ?? null;
            if (is_string($code)) {
                $roles[] = $code;
            }
        }

        $id = $row->id;
        $accountEmail = $row->email;
        $passwordHash = $row->password_hash;
        $status = $row->status;

        if (
            (!is_int($id) && (!is_string($id) || !ctype_digit($id)))
            || !is_string($accountEmail)
            || !is_string($passwordHash)
            || !is_string($status)
        ) {
            throw new \UnexpectedValueException('Administrator account data has an invalid format.');
        }

        return new AdminAccountCredentials(
            (int) $id,
            $accountEmail,
            $passwordHash,
            $status,
            $roles,
        );
    }

    public function createAdministrator(string $email, string $passwordHash): int
    {
        $result = $this->database->getConnection()->transaction(function () use ($email, $passwordHash): int {
            $this->database->table('admin_user')->insert([
                'email' => $email,
                'password_hash' => $passwordHash,
                'status' => 'active',
            ]);

            // Resolve the new ID through the unique email key instead of relying
            // on driver-specific lastInsertId() return semantics.
            $createdUser = $this->database->table('admin_user')
                ->where('email', $email)
                ->fetch();

            if ($createdUser === null) {
                throw new \UnexpectedValueException('The created administrator could not be reloaded.');
            }

            $userId = $createdUser->id;

            if (
                (!is_int($userId) && (!is_string($userId) || !ctype_digit($userId)))
                || (int) $userId < 1
            ) {
                throw new \UnexpectedValueException('The created administrator ID has an invalid format.');
            }

            $role = $this->database->table('admin_role')->where('code', 'admin')->fetch();

            if ($role === null) {
                throw new \LogicException('The base administrator role has not been seeded.');
            }

            $roleId = $role->id;

            if (!is_int($roleId) && (!is_string($roleId) || !ctype_digit($roleId))) {
                throw new \UnexpectedValueException('The base administrator role ID has an invalid format.');
            }

            $this->database->table('admin_user_role')->insert([
                'admin_user_id' => (int) $userId,
                'admin_role_id' => (int) $roleId,
            ]);

            return (int) $userId;
        });

        if (!is_int($result)) {
            throw new \UnexpectedValueException('The administrator provisioning transaction returned an invalid result.');
        }

        return $result;
    }

    public function recordSuccessfulLogin(int $adminUserId): void
    {
        $this->database->query(
            'UPDATE admin_user SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?',
            $adminUserId,
        );
    }
}
