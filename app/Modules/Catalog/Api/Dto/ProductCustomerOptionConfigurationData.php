<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

/** Úplná administrační konfigurace zákaznických voleb jednoho produktu. */
final readonly class ProductCustomerOptionConfigurationData
{
    /** @param list<ProductCustomerOptionData> $options */
    public function __construct(public array $options) {}
}
