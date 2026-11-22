<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Api\\Dto;

/**
 * Veřejná data kategorie určená pro předvyplnění administrátorského editoru.
 */
final readonly class CategoryEditorData
{
    /**
     * @param array<string, array{name: string, description: string|null}> $translations
     */
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public int $sortOrder,
        public bool $isActive,
        public array $translations,
    ) {}
}
