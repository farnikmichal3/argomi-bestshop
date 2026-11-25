<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

interface ProductCustomerOptionWriter
{
    /**
     * Nahrazení celé konfigurace zákaznických voleb produktu.
     *
     * Každá položka může obsahovat existující id; bez id vznikne nový záznam.
     * Totéž platí pro hodnoty volby. Překlady jsou indexovány aktivním locale.
     *
     * @param list<array{
     *     id?:int,
     *     code:string,
     *     type:string,
     *     isRequired:bool,
     *     sortOrder:int,
     *     translations:array<string,string>,
     *     values:list<array{id?:int,code:string,sortOrder:int,translations:array<string,string>}>
     * }> $options
     */
    public function save(int $productId, array $options): void;
}
