<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Api;

use App\Modules\Catalog\Api\AttributeAdminReader;
use App\Modules\Catalog\Api\Dto\AttributeAdminItem;
use App\Modules\Catalog\Api\Dto\AttributeEditorData;
use App\Modules\Catalog\Application\AttributeAdminQueryService;
use App\Shared\Domain\Locale\Locale;

final readonly class AttributeAdminApiService implements AttributeAdminReader
{
    public function __construct(private AttributeAdminQueryService $queryService) {}

    public function findAll(Locale $locale): array
    {
        return $this->queryService->findAll($locale);
    }

    public function findForEdit(int $attributeId): ?AttributeEditorData
    {
        return $this->queryService->findForEdit($attributeId);
    }

    public function findUnitOptions(Locale $locale): array
    {
        return $this->queryService->findUnitOptions($locale);
    }
}
