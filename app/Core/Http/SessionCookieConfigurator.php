<?php

declare(strict_types=1);

namespace App\Core\Http;

use LogicException;

/**
 * Selects independent PHP session cookies and storage before Nette or Tracy can start a session.
 */
final class SessionCookieConfigurator
{
    public function __construct(
        private readonly ?string $sessionRoot = null,
    ) {}

    /**
     * @return array{
     *     http: array{cookiePath: string, cookieSecure: bool},
     *     session: array{cookieSamesite: 'Strict'|'Lax', savePath: string}
     * }
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
        $cookiePath = $isAdminRequest ? $adminPath : ($basePath === '' ? '/' : $basePath . '/');

        $sessionRoot = $this->sessionRoot ?? dirname(__DIR__, 3) . '/var/sessions';
        $sessionDirectory = rtrim($sessionRoot, '/\\') . DIRECTORY_SEPARATOR . ($isAdminRequest ? 'admin' : 'public');

        if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0700, true) && !is_dir($sessionDirectory)) {
            throw new LogicException(sprintf('Unable to create session storage directory "%s".', $sessionDirectory));
        }

        if (!is_writable($sessionDirectory)) {
            throw new LogicException(sprintf('Session storage directory "%s" is not writable.', $sessionDirectory));
        }

        session_name($isAdminRequest ? 'ARGOMI_ADMIN_SESSID' : 'ARGOMI_SESSID');
        session_save_path($sessionDirectory);
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
                'savePath' => $sessionDirectory,
            ],
        ];
    }
}
