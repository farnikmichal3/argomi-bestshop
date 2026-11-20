<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Diagnostics;

use App\Modules\Translation\Api\TranslationDiagnostics;
use Tracy\Debugger;

/**
 * Registruje interní Tracy panel bez úniku infrastrukturní závislosti do Bootstrapu.
 */
final class NetteTranslationDiagnosticsService implements TranslationDiagnostics
{
    public function __construct(
        private readonly MissingTranslationPanel $panel,
    ) {}

    public function register(): void
    {
        Debugger::getBar()->addPanel($this->panel);
    }
}
