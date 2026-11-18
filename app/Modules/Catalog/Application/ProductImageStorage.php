<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface ProductImageStorage
{
    /**
     * Uloží binární obsah pod bezpečným relativním klíčem.
     */
    public function write(string $key, string $contents): void;

    /**
     * Načte binární obsah existujícího obrázku.
     */
    public function read(string $key): string;

    /**
     * Smaže obrázek; neexistující klíč je bezpečně ignorován.
     */
    public function delete(string $key): void;

    /**
     * Ověří existenci běžného souboru pod daným klíčem.
     */
    public function exists(string $key): bool;
}
