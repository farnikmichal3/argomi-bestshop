<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Diagnostics;

use App\Modules\Translation\Application\Dto\MissingTranslation;
use App\Modules\Translation\Application\MissingTranslationReporter;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Psr\Log\LoggerInterface;

/**
 * Deduplicuje chybějící klíče v rámci požadavku, loguje je a zpřístupňuje Tracy panelu.
 */
final class MissingTranslationDiagnostics implements MissingTranslationReporter
{
    /** @var array<string, MissingTranslation> */
    private array $missing = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function report(MessageDomain $domain, Locale $locale, string $messageKey): void
    {
        $id = $domain->value . "\0" . $locale->getCode() . "\0" . $messageKey;

        if (isset($this->missing[$id])) {
            return;
        }

        $missing = new MissingTranslation($domain, $locale->getCode(), $messageKey);
        $this->missing[$id] = $missing;

        $this->logger->warning('Missing system translation.', [
            'domain' => $domain->value,
            'locale' => $locale->getCode(),
            'message_key' => $messageKey,
        ]);
    }

    /** @return list<MissingTranslation> */
    public function getMissing(): array
    {
        return array_values($this->missing);
    }
}
