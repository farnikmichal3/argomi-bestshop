<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\DI;

use Nette\DI\CompilerExtension;

final class MediaExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        // Media services are registered by the shared project service search.
    }
}
