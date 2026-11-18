<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use RuntimeException;

final class DefaultLanguageCannotBeDeactivatedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Výchozí jazyk obchodu nelze deaktivovat.');
    }
}
