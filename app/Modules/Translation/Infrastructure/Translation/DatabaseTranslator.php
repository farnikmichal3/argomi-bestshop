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
 * Parametrizované zprávy formátuje pomocí ICU podle locale katalogu.
 */
final readonly class DatabaseTranslator implements Translator
{
    public function __construct(
        private MessageCatalog $catalog,
    ) {}

    /**
     * @param mixed $message Stabilní klíč systémového textu; očekává se string nebo Stringable.
     * @param mixed ...$parameters Pojmenované parametry v jednom poli nebo poziční parametry.
     */
    public function translate($message, ...$parameters): string
    {
        if (!is_string($message) && !$message instanceof Stringable) {
            throw new InvalidArgumentException(
                'Translation message must be a string or Stringable value.',
            );
        }

        $messageKey = (string) $message;
        $pattern = $this->catalog->get($messageKey);

        if ($pattern === null || $parameters === []) {
            return $pattern ?? $messageKey;
        }

        $arguments = $this->normalizeParameters($parameters);

        if ($arguments === []) {
            return $pattern;
        }

        $locale = $this->catalog->locale->getCode();
        $formatted = \MessageFormatter::formatMessage($locale, $pattern, $arguments);

        if ($formatted === false) {
            throw new MessageFormattingException(
                $messageKey,
                $locale,
                intl_get_error_message(),
            );
        }

        return $formatted;
    }

    /**
     * Připraví argumenty pro ICU a odmítne hodnoty s nejednoznačným převodem.
     *
     * Jediné předané pole se chápe jako mapa pojmenovaných nebo pozičních
     * argumentů. Samostatné argumenty se převedou na indexy 0, 1, 2...
     *
     * @param list<mixed> $parameters
     * @return array<int|string, string|int|float|null>
     */
    private function normalizeParameters(array $parameters): array
    {
        $arguments = count($parameters) === 1 && is_array($parameters[0])
            ? $parameters[0]
            : $parameters;

        $normalized = [];

        foreach ($arguments as $key => $value) {
            if ($value instanceof Stringable) {
                $value = (string) $value;
            }

            if (!is_string($value) && !is_int($value) && !is_float($value) && $value !== null) {
                throw new InvalidArgumentException(sprintf(
                    'Translation parameter "%s" must be a string, number, null, or Stringable value.',
                    (string) $key,
                ));
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
