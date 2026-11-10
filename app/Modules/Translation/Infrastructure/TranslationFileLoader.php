<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;

final class TranslationFileLoader
{
    /**
     * @param list<string> $modules
     */
    public function __construct(
        private readonly string $appDir,
        private readonly array $modules,
    ) {
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function load(string $locale): array
    {
        if (preg_match('/\\A[a-z]{2,3}(?:-[A-Z]{2})?\\z/', $locale) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid translation locale "%s".', $locale));
        }

        $translations = [];

        foreach ($this->modules as $module) {
            $file = $this->appDir . '/Modules/' . $module . '/Resources/translations/' . $locale . '.json';

            if (!is_file($file)) {
                continue;
            }

            $contents = file_get_contents($file);
            if ($contents === false) {
                throw new RuntimeException(sprintf('Unable to read translation file "%s".', $file));
            }

            try {
                $decoded = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException(sprintf('Invalid JSON in translation file "%s".', $file), previous: $exception);
            }

            if (!$decoded instanceof stdClass) {
                throw new RuntimeException(sprintf('Translation file "%s" must contain a JSON object.', $file));
            }

            $messages = [];

            foreach (get_object_vars($decoded) as $key => $message) {
                if (!is_string($key) || preg_match('/\\A[a-zA-Z0-9][a-zA-Z0-9._-]*\\z/', $key) !== 1) {
                    throw new RuntimeException(sprintf('Invalid translation key "%s" in "%s".', (string) $key, $file));
                }

                if (!is_string($message) || trim($message) === '') {
                    throw new RuntimeException(sprintf('Translation "%s" in "%s" must be a non-empty string.', $key, $file));
                }

                $messages[$key] = $message;
            }

            $translations[$module] = $messages;
        }

        return $translations;
    }
}
