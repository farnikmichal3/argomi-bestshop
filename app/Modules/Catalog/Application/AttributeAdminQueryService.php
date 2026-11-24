<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\Dto\AttributeAdminItem;
use App\Modules\Catalog\Api\Dto\AttributeEditorData;
use App\Modules\Catalog\Api\Dto\AttributeValueEditorData;
use App\Modules\Catalog\Domain\Attribute\AttributeType;
use App\Shared\Domain\Locale\Locale;

final readonly class AttributeAdminQueryService
{
    public function __construct(private AttributeAdminRepository $repository) {}

    /** @return list<AttributeAdminItem> */
    public function findAll(Locale $locale): array
    {
        return $this->repository->findAll($locale);
    }

    public function findForEdit(int $attributeId): ?AttributeEditorData
    {
        $data = $this->repository->findForEdit($attributeId);
        if ($data === null) {
            return null;
        }

        $values = array_map(
            static fn (array $value): AttributeValueEditorData => new AttributeValueEditorData(
                $value['id'],
                $value['code'],
                $value['sortOrder'],
                $value['translations'],
            ),
            $data['values'],
        );

        return new AttributeEditorData(
            $data['id'],
            $data['code'],
            AttributeType::from($data['type']),
            $data['unitId'],
            $data['isFilterable'],
            $data['sortOrder'],
            $data['translations'],
            $values,
            $data['isUsed'],
        );
    }

    /** @return array<int|string, string> */
    public function findUnitOptions(Locale $locale): array
    {
        return $this->repository->findUnitOptions($locale);
    }
}
