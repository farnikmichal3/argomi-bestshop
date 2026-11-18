<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Diagnostics;

use Tracy\IBarPanel;

/**
 * Vývojový Tracy panel pro chybějící systémové překlady aktuálního požadavku.
 */
final class MissingTranslationPanel implements IBarPanel
{
    public function __construct(
        private readonly MissingTranslationDiagnostics $diagnostics,
    ) {}

    public function getTab(): string
    {
        $count = count($this->diagnostics->getMissing());

        return '<span title="Missing system translations">Translations: ' . $count . '</span>';
    }

    public function getPanel(): string
    {
        $html = '<h1>Missing system translations</h1>';
        $missing = $this->diagnostics->getMissing();

        if ($missing === []) {
            return $html . '<p>No missing translations.</p>';
        }

        $html .= '<table><thead><tr><th>Domain</th><th>Locale</th><th>Key</th></tr></thead><tbody>';

        foreach ($missing as $item) {
            $html .= '<tr><td>' . $this->escape($item->domain->value) . '</td>';
            $html .= '<td>' . $this->escape($item->locale) . '</td>';
            $html .= '<td><code>' . $this->escape($item->messageKey) . '</code></td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
