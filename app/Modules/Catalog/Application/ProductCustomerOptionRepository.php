<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

interface ProductCustomerOptionRepository
{
    public function productExists(int $productId): bool;

    /**
     * @return list<array{
     *     id:int,
     *     code:string,
     *     type:string,
     *     isRequired:bool,
     *     sortOrder:int,
     *     translations:array<string,string>,
     *     values:list<array{id:int,code:string,sortOrder:int,translations:array<string,string>}>
     * }>
     */
    public function findForProduct(int $productId): array;

    public function optionUsedInCart(int $optionId): bool;

    public function valueUsedInCart(int $valueId): bool;

    public function createOption(int $productId, string $code, string $type, bool $isRequired, int $sortOrder): int;

    public function updateOption(int $productId, int $optionId, string $code, string $type, bool $isRequired, int $sortOrder): void;

    public function deleteOption(int $productId, int $optionId): void;

    public function createValue(int $optionId, string $code, int $sortOrder): int;

    public function updateValue(int $optionId, int $valueId, string $code, int $sortOrder): void;

    public function deleteValue(int $optionId, int $valueId): void;

    public function saveOptionTranslation(int $optionId, string $locale, string $name): void;

    public function saveValueTranslation(int $valueId, string $locale, string $label): void;
}
