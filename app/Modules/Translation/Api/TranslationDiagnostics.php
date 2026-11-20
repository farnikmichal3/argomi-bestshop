<?php

declare(strict_types=1);

namespace App\Modules\Translation\Api;

/**
 * Veřejný vstup pro registraci vývojové diagnostiky modulu Translation.
 */
interface TranslationDiagnostics
{
    public function register(): void;
}
