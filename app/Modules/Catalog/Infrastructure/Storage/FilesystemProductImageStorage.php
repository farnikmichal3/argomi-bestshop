<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Storage;

use App\Modules\Catalog\Application\ProductImageStorage;
use RuntimeException;
use InvalidArgumentException;

final class FilesystemProductImageStorage implements ProductImageStorage
{
    public function __construct(
        private readonly string $rootDirectory,
    ) {
        if (trim($this->rootDirectory) === '') {
            throw new InvalidArgumentException('Kořenový adresář úložiště obrázků nesmí být prázdný.');
        }
    }

    public function write(string $key, string $contents): void
    {
        $path = $this->pathFor($key, true);
        if ($path === null) {
            throw new RuntimeException('Nelze připravit adresář úložiště obrázků.');
        }

        if (is_link($path)) {
            throw new RuntimeException('Úložiště obrázků odmítlo symbolický odkaz.');
        }

        $temporaryPath = tempnam(dirname($path), '.image-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Nepodařilo se vytvořit dočasný soubor obrázku.');
        }

        try {
            $writtenBytes = file_put_contents($temporaryPath, $contents, LOCK_EX);
            if ($writtenBytes !== strlen($contents)) {
                throw new RuntimeException('Nepodařilo se kompletně zapsat obrázek do úložiště.');
            }

            if (!rename($temporaryPath, $path)) {
                throw new RuntimeException('Nepodařilo se atomicky uložit obrázek.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function read(string $key): string
    {
        $path = $this->pathFor($key, false);
        if ($path === null || is_link($path) || !is_file($path)) {
            throw new RuntimeException('Požadovaný obrázek v úložišti neexistuje.');
        }

        $realPath = realpath($path);
        $root = $this->rootPath(false);
        if ($realPath === false || $root === null || !$this->isInsideRoot($realPath, $root)) {
            throw new RuntimeException('Cesta obrázku opustila kořenový adresář úložiště.');
        }

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            throw new RuntimeException('Nepodařilo se načíst obrázek z úložiště.');
        }

        return $contents;
    }

    public function delete(string $key): void
    {
        $path = $this->pathFor($key, false);
        if ($path === null) {
            return;
        }

        if (is_link($path)) {
            throw new RuntimeException('Úložiště obrázků odmítlo symbolický odkaz.');
        }

        if (!file_exists($path)) {
            return;
        }

        $realPath = realpath($path);
        $root = $this->rootPath(false);
        if ($realPath === false || $root === null || !$this->isInsideRoot($realPath, $root) || !is_file($realPath)) {
            throw new RuntimeException('Cesta obrázku není běžným souborem uvnitř úložiště.');
        }

        if (!unlink($realPath)) {
            throw new RuntimeException('Nepodařilo se odstranit obrázek z úložiště.');
        }
    }

    public function exists(string $key): bool
    {
        $path = $this->pathFor($key, false);
        if ($path === null || is_link($path) || !is_file($path)) {
            return false;
        }

        $realPath = realpath($path);
        $root = $this->rootPath(false);

        return $realPath !== false && $root !== null && $this->isInsideRoot($realPath, $root);
    }

    /**
     * @throws InvalidArgumentException Pokud klíč není bezpečná relativní cesta.
     */
    private function pathFor(string $key, bool $createDirectories): ?string
    {
        $segments = explode('/', $key);
        if (
            $key === ''
            || str_contains($key, '\\')
            || str_contains($key, "\0")
            || str_starts_with($key, '/')
            || preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*(?:\/[a-zA-Z0-9][a-zA-Z0-9._-]*)*\z/D', $key) !== 1
        ) {
            throw new InvalidArgumentException('Klíč obrázku musí být bezpečná relativní cesta s oddělovačem /.');
        }

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Klíč obrázku nesmí obsahovat tečkové adresářové segmenty.');
            }
        }

        $root = $this->rootPath($createDirectories);
        if ($root === null) {
            return null;
        }

        $directory = $root;
        $directorySegments = array_slice($segments, 0, -1);
        foreach ($directorySegments as $segment) {
            $directory .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($directory)) {
                throw new RuntimeException('Úložiště obrázků odmítlo symbolický odkaz v cestě.');
            }

            if (!is_dir($directory)) {
                if (!$createDirectories) {
                    return null;
                }

                if (!mkdir($directory, 0770) && !is_dir($directory)) {
                    throw new RuntimeException('Nepodařilo se vytvořit adresář úložiště obrázků.');
                }
            }

            $realDirectory = realpath($directory);
            if ($realDirectory === false || !$this->isInsideRoot($realDirectory, $root)) {
                throw new RuntimeException('Adresář obrázku opustil kořenový adresář úložiště.');
            }
            $directory = $realDirectory;
        }

        return $directory . DIRECTORY_SEPARATOR . $segments[array_key_last($segments)];
    }

    private function rootPath(bool $create): ?string
    {
        if (!is_dir($this->rootDirectory)) {
            if (!$create) {
                return null;
            }

            if (!mkdir($this->rootDirectory, 0770, true) && !is_dir($this->rootDirectory)) {
                throw new RuntimeException('Nepodařilo se vytvořit kořenový adresář úložiště obrázků.');
            }
        }

        $root = realpath($this->rootDirectory);
        if ($root === false || !is_dir($root)) {
            throw new RuntimeException('Kořenový adresář úložiště obrázků není dostupný.');
        }

        return $root;
    }

    private function isInsideRoot(string $path, string $root): bool
    {
        return str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }
}
