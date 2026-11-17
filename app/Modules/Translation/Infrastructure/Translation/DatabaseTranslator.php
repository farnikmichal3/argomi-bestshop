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
 * Doména a požadované locale jsou pevně určeny při vytvoření překladače.
 * Chybějící text hledá v katalogu výchozího jazyka; pokud chybí i tam,
 * vrací stabilní klíč. ICU formátování používá locale nalezeného textu.
 */
final readonly class DatabaseTranslator implements Translator
{
    public function __construct(
        private MessageCatalog $catalog,
        private ?MessageCatalog $fallbackCatalog = null,
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
        $formatLocale = $this->catalog->locale->getCode();

        if ($pattern === null && $this->fallbackCatalog !== null) {
            $pattern = $this->fallbackCatalog->get($messageKey);
            $formatLocale = $this->fallbackCatalog->locale->getCode();
        }

        if ($pattern === null || $parameters === []) {
            return $pattern ?? $messageKey;
        }

        $arguments = $this->normalizeParameters($parameters);

        if ($arguments === []) {
            return $pattern;
        }

        $locale = $formatLocale;
        $formatter = $this->createFormatter($locale, $pattern);

        if (!$formatter instanceof \MessageFormatter) {
            throw new MessageFormattingException(
                $messageKey,
                $locale,
                intl_get_error_message(),
            );
        }

        $formatted = $formatter->format($arguments);

        if ($formatter->getErrorCode() !== U_ZERO_ERROR) {
            throw new MessageFormattingException(
                $messageKey,
                $locale,
                $formatter->getErrorMessage(),
            );
        }

        return $formatted;
    }

    /**
     * V některých běhových verzích intl může create() při neplatném vzoru
     * vrátit null, přestože PHPStan pro interní funkci předpokládá objekt.
     *
     * @return mixed Výsledek ICU factory včetně případného null při chybě.
     */
    private function createFormatter(string $locale, string $pattern): mixed
    {
        return \MessageFormatter::create($locale, $pattern);
    }

    /**
     * Připraví argumenty pro ICU a odmítne hodnoty s nejednoznačným převodem.
     *
     * Jediné předané pole se chápe jako mapa pojmenovaných nebo pozičních
     * argumentů. Samostatné argumenty se převedou na indexy 0, 1, 2...
     *
     * @param array<int|string, mixed> $parameters
     * @return array<int|string, string|int|float|null>
     */
    private function normalizeParameters(array $parameters): array
    {
        $values = array_values($parameters);
        $arguments = count($values) === 1 && is_array($values[0])
            ? $values[0]
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
