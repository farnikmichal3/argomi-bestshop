<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\Dto\CategoryTreeDto;
use App\Shared\Domain\Locale\Locale;

final class CategoryManagementService
{
    public function __construct(
        private readonly CategoryRepository $repository,
        private readonly CatalogPermission $permission,
    ) {}

    /**
     * @return list<CategoryTreeDto>
     */
    public function findTree(Locale $locale): array
    {
        if (!$this->permission->canView()) {
            throw new CatalogAccessDeniedException();
        }

        return $this->repository->findTree($locale);
    }
}
