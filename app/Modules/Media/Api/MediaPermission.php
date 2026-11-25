<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

interface MediaPermission
{
    public function canView(): bool;

    public function canManage(): bool;
}
