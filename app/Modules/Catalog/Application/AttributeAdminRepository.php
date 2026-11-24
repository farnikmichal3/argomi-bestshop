<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\Dto\AttributeAdminItem;
use App\Shared\Domain\Locale\Locale;

interface AttributeAdminRepository
{
    /** @return list<AttributeAdminItem> */
    public function findAll(Locale $locale): array;

    /**
     * @return array{
     *   id:int, code:string, type:string, unitId:?int, isFilterable:bool,
     *   sortOrder:int, translations:array<string,string>,
     *   values:list<array{id:int,code:string,sortOrder:int,translations:array<string,string>}>,
     *   isUsed:bool
     * }|null
     */
    public function findForEdit(int $attributeId): ?array;

    /** @return array<int|string, string> */
    public function findUnitOptions(Locale $locale): array;
}
