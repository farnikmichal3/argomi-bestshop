<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application\Dto;

/**
 * Výsledný bezpečný stav jednoho systémového překladu po porovnání zdroje.
 */
final readonly class SystemMessageState
{
    public function __construct(
        public string $messageText,
        public string $source,
        public bool $isCustomized,
        public ?string $defaultHash,
        public bool $isOutdated,
    ) {}
}
