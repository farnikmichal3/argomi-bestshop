<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\Dto\CategoryEditorData;
use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Shared\Domain\Locale\LocaleProvider;

final class CategoryEditorQueryService
{
    public function __construct(
        private readonly CategoryEditorRepository $repository,
        private readonly LocaleProvider $localeProvider,
        private readonly CatalogPermission $permission,
    ) {}

    public function findForEdit(int $categoryId): ?CategoryEditorData
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($categoryId < 1) {
            return null;
        }

        $category = $this->repository->findCategory($categoryId);
        if ($category === null) {
            return null;
        }

        $availableTranslations = [];
        foreach ($this->repository->findTranslations($categoryId) as $translation) {
            $availableTranslations[$translation['locale']] = [
                'name' => $translation['name'],
                'description' => $translation['description'],
            ];
        }

        $translations = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $translations[$code] = $availableTranslations[$code] ?? ['name' => '', 'description' => null];
        }

        return new CategoryEditorData(
            $categoryId,
            $category['parent_id'],
            $category['code'],
            $category['sort_order'],
            $category['is_active'],
            $translations,
        );
    }
}
