<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Dto;

enum VariantAvailabilityStatus: string
{
    case Tracked = 'tracked';
    case Untracked = 'untracked';
    case MissingStockRecord = 'missing_stock_record';
}
