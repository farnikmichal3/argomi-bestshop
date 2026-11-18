<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api\Dto;

/**
 * Jazyk obchodu včetně stavu aktivace a výchozího příznaku.
 */
final readonly class ManagedLocale
{
    public function __construct(
        public string $code,
        public string $name,
        public bool $isActive,
        public bool $isDefault,
        public int $sortOrder,
    ) {}
}
