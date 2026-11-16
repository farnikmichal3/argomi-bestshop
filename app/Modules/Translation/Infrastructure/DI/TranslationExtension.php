<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\DI;

use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageStore;
use Nette\DI\CompilerExtension;

/**
 * DI extension modulu Translation.
 *
 * Bootstrap ji registruje podle ModuleRegistry a zavedené konvence názvů.
 * Modul zde zpřístupňuje databázové úložiště přes jeho aplikační kontrakt.
 */
final class TranslationExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $this->getContainerBuilder()
            ->addDefinition($this->prefix('messageStore'))
            ->setType(DatabaseMessageStore::class);
    }
}
