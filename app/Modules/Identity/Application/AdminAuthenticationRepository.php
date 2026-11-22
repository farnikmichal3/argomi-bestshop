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

        return new AdminAccountCredentials(
            (int) $row->id,
            (string) $row->email,
            (string) $row->password_hash,
            (string) $row->status,
            $roles,
        );
    }

    public function recordSuccessfulLogin(int $adminUserId): void
    {
        $this->database->query(
            'UPDATE admin_user SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?',
            $adminUserId,
        );
    }
}
