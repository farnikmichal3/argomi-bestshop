<?php

declare(strict_types=1);

namespace App\Modules\Tax\Api;

use App\Modules\Tax\Api\Dto\TaxClassOption;

interface TaxClassOptionReader
{
    /** @return list<TaxClassOption> */
    public function findOptions(): array;
}
