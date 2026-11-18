<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

use RuntimeException;

final class LanguageNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Jazyk obchodu nebyl nalezen.');
    }
}
