<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Persistence;

use App\Modules\Translation\Api\CustomMessageNotFoundException;
use App\Modules\Translation\Api\MessageTranslationWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Application\MessageCacheInvalidator;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use InvalidArgumentException;
use Nette\Database\Explorer;

/**
 * Zapisuje pouze řádky source=custom a po úspěšné změně invaliduje katalogovou cache.
 */
final class DatabaseMessageTranslationWriter implements MessageTranslationWriter
{
    public function __construct(
        private readonly Explorer $database,
        private readonly MessageCacheInvalidator $cacheInvalidator,
    ) {}

    public function createCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
        string $messageText,
    ): void {
        $this->validate($messageKey, $messageText);

        $this->database->table($this->tableFor($domain))->insert([
            'message_key' => $messageKey,
            'locale' => $locale->getCode(),
            'message_text' => $messageText,
            'source' => 'custom',
            'is_customized' => 0,
            'default_hash' => null,
            'is_outdated' => 0,
        ]);

        $this->invalidate($domain, $locale);
    }

    public function updateCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
        string $messageText,
    ): void {
        $this->validate($messageKey, $messageText);

        $selection = $this->database->table($this->tableFor($domain))
            ->where('message_key', $messageKey)
            ->where('locale', $locale->getCode())
            ->where('source', 'custom');

        if ($selection->fetch() === null) {
            throw new CustomMessageNotFoundException();
        }

        $selection->update([
            'message_text' => $messageText,
        ]);

        $this->invalidate($domain, $locale);
    }

    public function deleteCustom(
        TranslationDomain $domain,
        string $messageKey,
        Locale $locale,
    ): void {
        $selection = $this->database->table($this->tableFor($domain))
            ->where('message_key', $messageKey)
            ->where('locale', $locale->getCode())
            ->where('source', 'custom');

        if ($selection->fetch() === null) {
            throw new CustomMessageNotFoundException();
        }

        $selection->delete();
        $this->invalidate($domain, $locale);
    }

    private function validate(string $messageKey, string $messageText): void
    {
        if (
            preg_match('/^[a-z][a-z0-9_.-]{0,189}$/D', $messageKey) !== 1
            || trim($messageText) === ''
            || strip_tags($messageText) !== $messageText
        ) {
            throw new InvalidArgumentException('Vlastní překlad musí mít platný klíč a neprázdný prostý text.');
        }
    }

    private function tableFor(TranslationDomain $domain): string
    {
        return match ($domain) {
            TranslationDomain::Catalog => 'catalog_message_translation',
            TranslationDomain::Cart => 'cart_message_translation',
            TranslationDomain::Checkout => 'checkout_message_translation',
            TranslationDomain::Admin => 'admin_message_translation',
        };
    }

    private function invalidate(TranslationDomain $domain, Locale $locale): void
    {
        $messageDomain = match ($domain) {
            TranslationDomain::Catalog => MessageDomain::Catalog,
            TranslationDomain::Cart => MessageDomain::Cart,
            TranslationDomain::Checkout => MessageDomain::Checkout,
            TranslationDomain::Admin => MessageDomain::Admin,
        };

        $this->cacheInvalidator->invalidate($messageDomain, $locale);
    }
}
