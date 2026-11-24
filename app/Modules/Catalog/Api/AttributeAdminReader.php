<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use App\Modules\Catalog\Api\Dto\AttributeAdminItem;
use App\Modules\Catalog\Api\Dto\AttributeEditorData;
use App\Shared\Domain\Locale\Locale;

interface AttributeAdminReader
{
    /** @return list<AttributeAdminItem> */
    public function findAll(Locale $locale): array;

    public function findForEdit(int $attributeId): ?AttributeEditorData;

    /** @return array<int|string, string> */
    public function findUnitOptions(): array;
}
