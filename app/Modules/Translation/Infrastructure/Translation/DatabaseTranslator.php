<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Translation;

use App\Modules\Translation\Application\Dto\MessageCatalog;
use InvalidArgumentException;
use Nette\Localization\Translator;
use Stringable;

/**
 * Překládá stabilní klíče z jednoho neměnného katalogu.
 *
 * Doména a locale jsou pevně určeny při vytvoření překladače. Chybějící
 * klíč se zatím vrací beze změny; databázový fallback doplní F4-06.
 * Parametry jsou přijaty kvůli kontraktu Nette, jejich ICU formátování
 * však zajišťuje až navazující krok F4-05.
 */
final readonly class DatabaseTranslator implements Translator
{
    public function __construct(
        private MessageCatalog $catalog,
    ) {}

    /**
     * @param mixed $message Stabilní klíč systémového textu; očekává se string nebo Stringable.
     * @param mixed ...$parameters Parametry pro budoucí formátování zprávy.
     */
    public function translate($message, ...$parameters): string
    {
        if (!is_string($message) && !$message instanceof Stringable) {
            throw new InvalidArgumentException(
                'Translation message must be a string or Stringable value.',
            );
        }

        $messageKey = (string) $message;

        return $this->catalog->get($messageKey) ?? $messageKey;
    }
}
