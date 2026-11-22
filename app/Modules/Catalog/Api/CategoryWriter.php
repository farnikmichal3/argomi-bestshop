<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Veřejné API pro vytvoření a úpravu kategorií.
 *
 * Administrátorské UI zapisuje do katalogu výhradně přes tento kontrakt,
 * bez závislosti na aplikačních službách modulu.
 */
interface CategoryWriter
{
    /**
     * @param array<string, mixed> $translations
     */
    public function create(
        ?int $parentId,
        string $code,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): int;

    /**
     * @param array<string, mixed> $translations
     */
    public function update(
        int $categoryId,
        ?int $parentId,
        string $code,
        int $sortOrder,
        bool $isActive,
        array $translations,
    ): void;
}
