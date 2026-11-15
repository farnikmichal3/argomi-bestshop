<?php

declare(strict_types=1);

namespace App\UI\Admin\Development\SqlDiagnostics;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use Tracy\Debugger;

final class SqlDiagnosticsPresenter extends Presenter
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    protected function startup(): void
    {
        parent::startup();

        // SQL diagnostika nesmí být dostupná ani při přímém zadání URL mimo development.
        if (!Debugger::isEnabled()) {
            $this->error();
        }
    }

    public function renderDefault(): void
    {
        // Nečteme žádná obchodní data; dotaz pouze ověří funkční spojení a vykreslí se v Tracy panelu.
        $this->template->activeLanguageCount = $this->database->table('language')
            ->where('is_active', 1)
            ->count();
    }
}
