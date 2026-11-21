<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Veřejná, prezentačně nezávislá položka stromu kategorií.
 */
final readonly class CategoryTreeItem
{
    /**
     * @param list<CategoryTreeItem> $children
     */
    public function __construct(
        public int $id,
        public string $code,
        public ?string $name,
        public bool $isActive,
        public array $children,
    ) {}
}
