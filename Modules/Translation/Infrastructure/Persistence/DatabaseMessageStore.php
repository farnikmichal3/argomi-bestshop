<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Persistence;

use App\Modules\Translation\Application\Dto\MessageCatalog;
use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Načítá systémové texty z tabulky vlastnické domény.
 *
 * Pro každé volání provede jediný SELECT filtrovaný podle locale.
 * Databázové řádky se mapují na typovaný katalog uvnitř persistence vrstvy;
 * ActiveRow nikdy neopouští infrastrukturu.
 */
final class DatabaseMessageStore implements MessageStore
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function load(MessageDomain $domain, Locale $locale): MessageCatalog
    {
        $table = match ($domain) {
            MessageDomain::Catalog => 'catalog_message_translation',
            MessageDomain::Cart => 'cart_message_translation',
            MessageDomain::Checkout => 'checkout_message_translation',
            MessageDomain::Admin => 'admin_message_translation',
        };

        $rows = $this->database->table($table)
            ->where('locale', $locale->getCode())
            ->order('message_key ASC')
            ->fetchAll();

        $messages = [];

        foreach ($rows as $row) {
            [$messageKey, $messageText] = $this->mapMessage($row);
            $messages[$messageKey] = $messageText;
        }

        return new MessageCatalog($domain, $locale, $messages);
    }

    /**
     * Při mapování kontroluje typy, přestože databázové schéma hodnoty omezuje.
     *
     * @return array{string, string}
     * @throws UnexpectedValueException Pokud řádek obsahuje neočekávané typy.
     */
    private function mapMessage(ActiveRow $row): array
    {
        $messageKey = $row->message_key;
        $messageText = $row->message_text;

        if (!is_string($messageKey) || !is_string($messageText)) {
            throw new UnexpectedValueException(
                'Databázový překlad musí obsahovat textový klíč i textovou hodnotu.',
            );
        }

        return [$messageKey, $messageText];
    }
}
