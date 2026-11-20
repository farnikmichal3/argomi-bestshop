<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\DI;

use Nette\DI\CompilerExtension;

/**
 * DI composition root modulu Catalog.
 *
 * Konkrétní služby se registrují zde pouze tehdy, když jejich konfigurace
 * vyžaduje modulové nastavení nebo explicitní vazbu. Služby odpovídající
 * projektovým konvencím *Repository/*Factory/*Service zůstávají v automatickém
 * vyhledávání v config/services.neon; nesmějí se zde registrovat podruhé.
 */
final class CatalogExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        // Základní čtecí ProductRepository je automaticky registrován
        // konvencí služeb. Další modulová DI konfigurace přijde s use-cases
        // a službami v navazujících krocích F5.
    }
}
