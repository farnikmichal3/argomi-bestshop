<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\SlugRepository;
use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;

/**
 * Kontrola slugů přes Nette Database Explorer.
 *
 * Tabulka a sloupec entity jsou vybírány výhradně z typovaného enumu,
 * nikoli z uživatelského vstupu.
 */
final class DatabaseSlugRepository implements SlugRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function isAvailable(
        SlugTarget $target,
        Locale $locale,
        string $slug,
        ?int $excludeEntityId = null,
    ): bool {
        [$table, $entityColumn] = match ($target) {
            SlugTarget::Product => ['product_translation', 'product_id'],
            SlugTarget::Category => ['category_translation', 'category_id'],
        };

        $selection = $this->database->table($table)
            ->where('locale', $locale->getCode())
            ->where('slug', $slug);

        if ($excludeEntityId !== null) {
            $selection->where($entityColumn . ' != ?', $excludeEntityId);
        }

        return $selection->fetch() === null;
    }
}
