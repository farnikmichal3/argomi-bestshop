<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\DI;

use Nette\DI\CompilerExtension;

/**
 * DI extension modulu Translation.
 *
 * Bootstrap ji registruje podle ModuleRegistry a zavedené konvence názvů.
 * Konkrétní služby budou přidávány v navazujících krocích fáze F4.
 */
final class TranslationExtension extends CompilerExtension {}
