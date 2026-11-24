<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Persistence;

use App\Modules\Translation\Api\Dto\MessageTranslation;
use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationMessageStatus;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Čte systémové překlady z pevně povolených vlastnických tabulek.
 *
 * Identifikátory tabulek nikdy nepřebírá od uživatele; filtry jsou předávány
 * přes API Nette Database Exploreru jako hodnoty.
 */
final class DatabaseMessageTranslationReader implements MessageTranslationReader
{
    /** @var array<string, TranslationDomain> */
    private const TABLES = [
        'catalog_message_translation' => TranslationDomain::Catalog,
        'cart_message_translation' => TranslationDomain::Cart,
        'checkout_message_translation' => TranslationDomain::Checkout,
        'admin_message_translation' => TranslationDomain::Admin,
    ];

    private const MAX_RESULTS = 200;

    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function search(
        ?TranslationDomain $domain = null,
        ?Locale $locale = null,
        ?TranslationMessageStatus $status = null,
    ): array {
        /** @var array<string, list<MessageTranslation>> $byDomain */
        $byDomain = [];

        foreach (self::TABLES as $table => $tableDomain) {
            if ($domain !== null && $domain !== $tableDomain) {
                continue;
            }

            $selection = $this->database->table($table);

            if ($locale !== null) {
                $selection->where('locale', $locale->getCode());
            }

            if ($status === TranslationMessageStatus::Core) {
                $selection->where('source', 'core');
            } elseif ($status === TranslationMessageStatus::Custom) {
                $selection->where('source', 'custom');
            } elseif ($status === TranslationMessageStatus::Customized) {
                $selection->where('source', 'core')->where('is_customized', 1);
            } elseif ($status === TranslationMessageStatus::Outdated) {
                $selection->where('source', 'core')->where('is_outdated', 1);
            }

            $byDomain[$tableDomain->value] = [];
            foreach ($selection->order('message_key ASC')->limit(self::MAX_RESULTS)->fetchAll() as $row) {
                $byDomain[$tableDomain->value][] = $this->map($tableDomain, $row);
            }
        }

        // A global alphabetical slice would let a busy owner (usually Catalog)
        // hide every result from later domains. Merge domains fairly first,
        // then apply the global cap and restore the public deterministic order.
        $translations = [];
        $offset = 0;
        do {
            $added = false;
            foreach ($byDomain as $domainTranslations) {
                if (isset($domainTranslations[$offset])) {
                    $translations[] = $domainTranslations[$offset];
                    $added = true;
                    if (count($translations) >= self::MAX_RESULTS) {
                        break 2;
                    }
                }
            }
            $offset++;
        } while ($added);

        usort(
            $translations,
            static fn(MessageTranslation $left, MessageTranslation $right): int
                => [$left->domain->value, $left->locale, $left->messageKey]
                <=> [$right->domain->value, $right->locale, $right->messageKey],
        );

        return $translations;
    }

    private function map(TranslationDomain $domain, ActiveRow $row): MessageTranslation
    {
        $messageKey = $row->message_key;
        $locale = $row->locale;
        $messageText = $row->message_text;
        $source = $row->source;
        $isCustomized = $row->is_customized;
        $isOutdated = $row->is_outdated;

        if (
            !is_string($messageKey)
            || !is_string($locale)
            || !is_string($messageText)
            || !is_string($source)
            || !in_array($source, ['core', 'custom'], true)
            || !in_array($isCustomized, [0, 1, '0', '1', false, true], true)
            || !in_array($isOutdated, [0, 1, '0', '1', false, true], true)
        ) {
            throw new UnexpectedValueException('Databázový překlad obsahuje neplatné hodnoty.');
        }

        return new MessageTranslation(
            $domain,
            $messageKey,
            $locale,
            $messageText,
            $source,
            (bool) $isCustomized,
            (bool) $isOutdated,
        );
    }
}
