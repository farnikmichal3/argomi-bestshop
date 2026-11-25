<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Storage;

use App\Modules\Media\Application\MediaFileStorage;
use InvalidArgumentException;
use RuntimeException;

final class FilesystemMediaFileStorage implements MediaFileStorage
{
    public function __construct(private readonly string $rootDirectory)
    {
        if (trim($rootDirectory) === '') {
            throw new InvalidArgumentException('Kořenový adresář Media nesmí být prázdný.');
        }
    }

    public function write(string $key, string $contents): void
    {
        $path = $this->pathFor($key, true);
        $temporary = tempnam(dirname($path), '.media-');
        if ($temporary === false) {
            throw new RuntimeException('Nelze vytvořit dočasný soubor média.');
        }

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
                throw new RuntimeException('Mediální soubor nebyl kompletně zapsán.');
            }
            if (!rename($temporary, $path)) {
                throw new RuntimeException('Mediální soubor nelze atomicky uložit.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    public function read(string $key): string
    {
        $path = $this->pathFor($key, false);
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('Mediální soubor neexistuje.');
        }
        $real = realpath($path);
        $root = $this->rootPath();
        if ($real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Cesta média opustila kořen úložiště.');
        }
        $contents = file_get_contents($real);
        if ($contents === false) {
            throw new RuntimeException('Mediální soubor nelze načíst.');
        }

        return $contents;
    }

    public function delete(string $key): void
    {
        $path = $this->pathFor($key, false);
        if (!file_exists($path)) {
            return;
        }
        if (is_link($path)) {
            throw new RuntimeException('Mediální úložiště odmítlo symbolický odkaz.');
        }
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, $this->rootPath() . DIRECTORY_SEPARATOR) || !is_file($real)) {
            throw new RuntimeException('Mediální soubor není běžným souborem uvnitř úložiště.');
        }
        if (!unlink($real)) {
            throw new RuntimeException('Mediální soubor nelze odstranit.');
        }
    }

    public function exists(string $key): bool
    {
        try {
            $path = $this->pathFor($key, false);
            $real = realpath($path);
            return $real !== false && is_file($real) && !is_link($path)
                && str_starts_with($real, $this->rootPath() . DIRECTORY_SEPARATOR);
        } catch (RuntimeException) {
            return false;
        }
    }

    private function pathFor(string $key, bool $create): string
    {
        if (
            $key === ''
            || str_contains($key, '\\')
            || str_contains($key, "\0")
            || preg_match('/\\A[a-zA-Z0-9][a-zA-Z0-9._-]*(?:\\/[a-zA-Z0-9][a-zA-Z0-9._-]*)*\\z/D', $key) !== 1
        ) {
            throw new InvalidArgumentException('Klíč média není bezpečná relativní cesta.');
        }
        $segments = explode('/', $key);
        $directory = $this->rootPath($create);
        foreach (array_slice($segments, 0, -1) as $segment) {
            $directory .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($directory)) {
                throw new RuntimeException('Mediální úložiště odmítlo symbolický odkaz v cestě.');
            }
            if (!is_dir($directory)) {
                if (!$create && !is_dir($directory)) {
                    throw new RuntimeException('Adresář média neexistuje.');
                }
                if (!mkdir($directory, 0770) && !is_dir($directory)) {
                    throw new RuntimeException('Adresář média nelze vytvořit.');
                }
            }
            $real = realpath($directory);
            if ($real === false || !str_starts_with($real, $this->rootPath() . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Adresář média opustil kořen úložiště.');
            }
            $directory = $real;
        }

        return $directory . DIRECTORY_SEPARATOR . $segments[array_key_last($segments)];
    }

    private function rootPath(bool $create = false): string
    {
        if (!is_dir($this->rootDirectory)) {
            if (!$create || (!mkdir($this->rootDirectory, 0770, true) && !is_dir($this->rootDirectory))) {
                throw new RuntimeException('Kořenový adresář Media není dostupný.');
            }
        }
        $root = realpath($this->rootDirectory);
        if ($root === false || !is_dir($root)) {
            throw new RuntimeException('Kořenový adresář Media není dostupný.');
        }

        return $root;
    }
}
