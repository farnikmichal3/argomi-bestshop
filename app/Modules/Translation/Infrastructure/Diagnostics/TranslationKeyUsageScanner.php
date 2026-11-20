<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Diagnostics;

use App\Modules\Translation\Api\TranslationDomain;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Hledá statické systémové překladové klíče v Latte šablonách a PHP presenterech.
 *
 * Doménu přebírá výhradně z nejbližšího presenteru odvozeného od
 * TranslatedPresenter. Dynamicky sestavené klíče záměrně neodhaduje.
 */
final class TranslationKeyUsageScanner
{
    public function __construct(
        private readonly string $uiDirectory,
    ) {}

    /**
     * @return array<string, array<string, true>> Doména => množina používaných klíčů.
     */
    public function scan(): array
    {
        if (!is_dir($this->uiDirectory)) {
            throw new RuntimeException('Adresář uživatelského rozhraní pro kontrolu překladů neexistuje.');
        }

        $usages = [];
        $domainByDirectory = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->uiDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if (!$file->isFile() || !in_array($file->getExtension(), ['latte', 'php'], true)) {
                continue;
            }

            $directory = $file->getPath();

            if (!array_key_exists($directory, $domainByDirectory)) {
                $domainByDirectory[$directory] = $this->resolveDomain($directory);
            }

            $domain = $domainByDirectory[$directory];

            if ($domain === null) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                throw new RuntimeException(
                    sprintf('Soubor "%s" nelze přečíst při kontrole překladů.', $file->getPathname()),
                );
            }

            foreach ($this->extractKeys($contents, $file->getExtension()) as $key) {
                $usages[$domain->value][$key] = true;
            }
        }

        ksort($usages);

        foreach ($usages as &$keys) {
            ksort($keys);
        }

        unset($keys);

        return $usages;
    }

    private function resolveDomain(string $directory): ?TranslationDomain
    {
        $root = rtrim($this->uiDirectory, DIRECTORY_SEPARATOR);
        $current = $directory;

        while ($current !== $root && str_starts_with($current, $root . DIRECTORY_SEPARATOR)) {
            $presenterFile = $current . DIRECTORY_SEPARATOR . basename($current) . 'Presenter.php';

            if (is_file($presenterFile)) {
                $source = file_get_contents($presenterFile);

                if ($source === false) {
                    throw new RuntimeException(
                        sprintf('Presenter "%s" nelze přečíst při kontrole překladů.', $presenterFile),
                    );
                }

                if (preg_match('/extends\s+([\\\\A-Za-z_][\\\\A-Za-z0-9_]*)/', $source, $extends) !== 1) {
                    return null;
                }

                $parentClass = ltrim($extends[1], '\\');
                $parentName = substr($parentClass, strrpos($parentClass, '\\') === false
                    ? 0
                    : strrpos($parentClass, '\\') + 1);

                if ($parentName !== 'TranslatedPresenter') {
                    return null;
                }

                if (preg_match(
                    '/function\s+getTranslationDomain\s*\([^)]*\)\s*:\s*TranslationDomain\s*\{\s*return\s+TranslationDomain::([A-Za-z_][A-Za-z0-9_]*)\s*;/s',
                    $source,
                    $match,
                ) !== 1) {
                    throw new RuntimeException(
                        sprintf('Presenter "%s" neurčuje statickou překladovou doménu.', $presenterFile),
                    );
                }

                foreach (TranslationDomain::cases() as $domain) {
                    if ($domain->name === $match[1]) {
                        return $domain;
                    }
                }

                throw new RuntimeException(
                    sprintf('Presenter "%s" používá neznámou překladovou doménu.', $presenterFile),
                );
            }

            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractKeys(string $contents, string $extension): array
    {
        if ($extension === 'latte') {
            preg_match_all("~\\{_\\s*(['\"])([A-Za-z0-9][A-Za-z0-9_.-]*)\\1~", $contents, $matches);
            $keys = array_values(array_unique($matches[2]));
            sort($keys);

            return $keys;
        }

        $tokens = token_get_all($contents);
        $keys = [];

        foreach ($tokens as $index => $token) {
            // Nette Forms dostávají překladové klíče jako statické řetězce v argumentech i option polích.
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                if (preg_match("/\\A(['\\\"])(translations\\.[A-Za-z0-9_.-]+)\\1\\z/", $token[1], $formMatch) === 1) {
                    $keys[] = $formMatch[2];
                }
            }

            if (!is_array($token) || !in_array($token[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                continue;
            }

            $methodIndex = $this->nextSignificantTokenIndex($tokens, $index + 1);

            if ($methodIndex === null || !is_array($tokens[$methodIndex]) || $tokens[$methodIndex][0] !== T_STRING || $tokens[$methodIndex][1] !== 'translate') {
                continue;
            }

            $openingIndex = $this->nextSignificantTokenIndex($tokens, $methodIndex + 1);

            if ($openingIndex === null || $tokens[$openingIndex] !== '(') {
                continue;
            }

            $argumentIndex = $this->nextSignificantTokenIndex($tokens, $openingIndex + 1);

            if ($argumentIndex === null || !is_array($tokens[$argumentIndex]) || $tokens[$argumentIndex][0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            if (preg_match("/\\A(['\"])([A-Za-z0-9][A-Za-z0-9_.-]*)\\1\\z/", $tokens[$argumentIndex][1], $match) === 1) {
                $keys[] = $match[2];
            }
        }

        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function nextSignificantTokenIndex(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $index;
        }

        return null;
    }
}
