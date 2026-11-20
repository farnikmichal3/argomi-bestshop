<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use RuntimeException;

/**
 * Signalizuje neúplnou konfiguraci obchodu bez nastavené výchozí měny.
 *
 * Výchozí měna je nutná pro bezpečné výpočty a ukládání cen.
 * Třída záměrně neobsahuje náhradní měnu, aby chyba konfigurace
 * nebyla skrytě zaměněna za platné obchodní nastavení.
 */
final class DefaultCurrencyNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Výchozí měna obchodu není nastavena nebo není aktivní.');
    }
}
