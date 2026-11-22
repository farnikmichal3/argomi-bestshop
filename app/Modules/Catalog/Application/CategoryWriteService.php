<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use InvalidArgumentException;

final class CategoryWriteService
{
    public function __construct(
        private readonly CategoryWriteRepository $repository,
        private readonly LocaleProvider $localeProvider,
        private readonly SlugService $slugService,
        private readonly Transaction $transaction,
        private readonly CatalogPermission $permission,
    ) {}

    /**
     * @param array<string, mixed> $translations
     */
    public function create(?int $parentId, string $code, int $sortOrder, bool $isActive, array $translations): int
    {
        $this->assertCanManage();
        $code = self::normalizeCode($code);
        $this->assertParent($parentId, null);
        $localized = $this->normalizeTranslations($translations);
        if (!$this->repository->isCodeAvailable($code)) {
            throw new InvalidArgumentException('Kód kategorie již existuje.');
        }

        return $this->transaction->run(function () use ($parentId, $code, $sortOrder, $isActive, $localized): int {
            $id = $this->repository->createCategory($parentId, $code, $sortOrder, $isActive);
            $this->saveTranslations($id, $localized);
            return $id;
        });
    }

    /**
     * @param array<string, mixed> $translations
     */
    public function update(int $categoryId, ?int $parentId, string $code, int $sortOrder, bool $isActive, array $translations): void
    {
        $this->assertCanManage();
        if ($categoryId < 1 || !$this->repository->parentExists($categoryId)) {
            throw new InvalidArgumentException('Kategorie nebyla nalezena.');
        }
        $code = self::normalizeCode($code);
        $this->assertParent($parentId, $categoryId);
        $localized = $this->normalizeTranslations($translations);
        if (!$this->repository->isCodeAvailable($code, $categoryId)) {
            throw new InvalidArgumentException('Kód kategorie již existuje.');
        }

        $this->transaction->run(function () use ($categoryId, $parentId, $code, $sortOrder, $isActive, $localized): void {
            $this->repository->updateCategory($categoryId, $parentId, $code, $sortOrder, $isActive);
            $this->saveTranslations($categoryId, $localized);
        });
    }

    public function delete(int $categoryId): void
    {
        $this->assertCanManage();
        if ($categoryId < 1) {
            throw new CategoryDeletionBlockedException(CategoryDeletionBlockedException::NOT_FOUND);
        }

        $this->transaction->run(function () use ($categoryId): void {
            if (!$this->repository->parentExists($categoryId)) {
                throw new CategoryDeletionBlockedException(CategoryDeletionBlockedException::NOT_FOUND);
            }
            if ($this->repository->hasChildren($categoryId)) {
                throw new CategoryDeletionBlockedException(CategoryDeletionBlockedException::HAS_CHILDREN);
            }
            if ($this->repository->hasProductAssignments($categoryId)) {
                throw new CategoryDeletionBlockedException(CategoryDeletionBlockedException::HAS_PRODUCTS);
            }

            $this->repository->deleteCategory($categoryId);
        });
    }

    /**
     * @param array<string, mixed> $translations
     * @return array<string, array{name: string, description: string|null}>
     */
    private function normalizeTranslations(array $translations): array
    {
        $normalized = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $entry = $translations[$code] ?? null;
            if (!is_array($entry) || !isset($entry['name']) || !is_string($entry['name'])) {
                throw new InvalidArgumentException(sprintf('Chybí název kategorie pro aktivní jazyk %s.', $code));
            }
            $name = trim($entry['name']);
            $description = $entry['description'] ?? null;
            if ($name === '' || mb_strlen($name) > 255 || preg_match('//u', $name) !== 1) {
                throw new InvalidArgumentException(sprintf('Název kategorie pro %s je neplatný.', $code));
            }
            if ($description !== null && (!is_string($description) || preg_match('//u', $description) !== 1)) {
                throw new InvalidArgumentException(sprintf('Popis kategorie pro %s je neplatný.', $code));
            }
            $normalized[$code] = ['name' => $name, 'description' => $description === null ? null : trim($description)];
        }
        if (count($translations) !== count($normalized)) {
            throw new InvalidArgumentException('Překlady mohou být zadány pouze pro aktivní jazyky.');
        }
        return $normalized;
    }

    /** @param array<string, array{name: string, description: string|null}> $translations */
    private function saveTranslations(int $categoryId, array $translations): void
    {
        foreach ($translations as $code => $entry) {
            $locale = Locale::fromString($code);
            $slug = $this->slugService->generate(SlugTarget::Category, $entry['name'], $locale, $categoryId);
            $this->repository->saveTranslation($categoryId, $locale, $entry['name'], $slug, $entry['description']);
        }
    }

    private function assertParent(?int $parentId, ?int $categoryId): void
    {
        if ($parentId === null) {
            return;
        }
        if ($parentId < 1 || !$this->repository->parentExists($parentId)) {
            throw new InvalidArgumentException('Vybraný rodičovský uzel neexistuje.');
        }
        if ($categoryId === null) {
            return;
        }
        if ($parentId === $categoryId) {
            throw new InvalidArgumentException('Kategorie nemůže být sama sobě rodičem.');
        }
        $seen = [];
        $ancestorId = $parentId;
        while ($ancestorId !== null) {
            if ($ancestorId === $categoryId || isset($seen[$ancestorId])) {
                throw new InvalidArgumentException('Změna rodiče by vytvořila cyklus v hierarchii kategorií.');
            }
            $seen[$ancestorId] = true;
            $ancestorId = $this->repository->findParentId($ancestorId);
        }
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
    }

    private static function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        if (preg_match('/\A[a-z0-9][a-z0-9_-]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód kategorie musí obsahovat 1 až 100 malých ASCII písmen, číslic, pomlček nebo podtržítek.');
        }
        return $code;
    }
}
