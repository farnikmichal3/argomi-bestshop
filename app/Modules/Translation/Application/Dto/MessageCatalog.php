<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application\Dto;

use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;

/**
 * Neměnná sada systémových textů jedné domény a jednoho jazyka.
 */
final readonly class MessageCatalog
{
    /**
     * @param array<string, string> $messages Mapování stabilního klíče na prostý text.
     */
    public function __construct(
        public MessageDomain $domain,
        public Locale $locale,
        private array $messages,
    ) {}

    /**
     * Vrátí překlad klíče nebo null, pokud v načtené sadě chybí.
     */
    public function get(string $messageKey): ?string
    {
        return $this->messages[$messageKey] ?? null;
    }

    /**
     * Vrátí všechny načtené texty bez možnosti změnit interní sadu.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->messages;
    }
}
