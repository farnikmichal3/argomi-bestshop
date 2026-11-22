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
     *     sessionName: string,
     *     http: array{cookiePath: string, cookieSecure: bool},
     *     session: array{cookieSamesite: 'Strict'|'Lax', savePath: string}
     * }
     */
    public function resolvePolicy(string $requestUri, string $scriptName, bool $secure): array
    {
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

        return [
            'sessionName' => $isAdminRequest ? 'ARGOMI_ADMIN_SESSID' : 'ARGOMI_SESSID',
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

        if (headers_sent($file, $line)) {
            throw new LogicException(sprintf(
                'The session cookie must be configured before output is sent (output started at %s:%d).',
                $file,
                $line,
            ));
        }

        $policy = $this->resolvePolicy($requestUri, $scriptName, $secure);

        session_name($policy['sessionName']);
        session_save_path($policy['session']['savePath']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $policy['http']['cookiePath'],
            'domain' => '',
            'secure' => $policy['http']['cookieSecure'],
            'httponly' => true,
            'samesite' => $policy['session']['cookieSamesite'],
        ]);

        unset($policy['sessionName']);

        return $policy;
    }
}
