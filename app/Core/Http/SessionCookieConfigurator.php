<?php

declare(strict_types=1);

namespace App\Core\Http;

use LogicException;

/**
 * Selects the independent PHP session cookie before Nette or Tracy can start a session.
 */
final class SessionCookieConfigurator
{
    /**
     * @return array{http: array{cookiePath: string, cookieSecure: bool}, session: array{cookieSamesite: 'Strict'|'Lax'}}
     */
    public function configure(string $requestUri, string $scriptName, bool $secure): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new LogicException('The session cookie must be configured before the PHP session starts.');
        }

        $requestPath = parse_url($requestUri, PHP_URL_PATH);

        if (!is_string($requestPath) || $requestPath === '') {
            throw new LogicException('Unable to determine the request path for session selection.');
        }

        $scriptDirectory = str_replace('\\', '/', dirname($scriptName));
        $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
        $adminPath = $basePath . '/admin';
        $isAdminRequest = $requestPath === $adminPath || str_starts_with($requestPath, $adminPath . '/');

        session_name($isAdminRequest ? 'ARGOMI_ADMIN_SESSID' : 'ARGOMI_SESSID');
        $cookiePath = $isAdminRequest ? $adminPath : ($basePath === '' ? '/' : $basePath . '/');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $isAdminRequest ? 'Strict' : 'Lax',
        ]);

        return [
            'http' => [
                'cookiePath' => $cookiePath,
                'cookieSecure' => $secure,
            ],
            'session' => [
                'cookieSamesite' => $isAdminRequest ? 'Strict' : 'Lax',
            ],
        ];
    }
}
