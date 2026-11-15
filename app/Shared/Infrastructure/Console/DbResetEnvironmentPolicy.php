<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

final readonly class DbResetEnvironmentPolicy
{
    public function __construct(
        private ?string $environment,
    ) {}

    public function isAllowed(): bool
    {
        return $this->environment === 'development';
    }
}
