<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Money;

use App\Shared\Domain\Money\Currency;
use App\Shared\Domain\Money\CurrencyProvider;
use App\Shared\Domain\Money\DefaultCurrencyNotFoundException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Načítá měny z tabulky currency pomocí Nette Database Explorer.
 *
 * Záznamy ActiveRow se mapují na doménový objekt přímo zde a nikdy
 * neopouštějí infrastrukturní vrstvu.
 */
final class DatabaseCurrencyProvider implements CurrencyProvider
{
    public function __construct(
        private readonly Explorer $database,
    ) {
    }

    /**
     * @throws DefaultCurrencyNotFoundException Pokud databáze nemá výchozí aktivní měnu.
     */
    public function getDefault(): Currency
    {
        $row = $this->database->table('currency')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->fetch();

        if ($row === null) {
            throw new DefaultCurrencyNotFoundException();
        }

        return $this->mapCurrency($row);
    }

    /**
     * @return list<Currency>
     */
    public function getActive(): array
    {
        $rows = $this->database->table('currency')
            ->where('is_active', 1)
            ->order('sort_order ASC, code ASC')
            ->fetchAll();

        $currencies = [];

        foreach ($rows as $row) {
            $currencies[] = $this->mapCurrency($row);
        }

        return $currencies;
    }

    /**
     * Převede databázový řádek na doménový objekt bez úniku ActiveRow.
     *
     * Hodnoty se kontrolují i přesto, že je omezuje databázové schéma.
     * Tím se případná nekonzistence nebo neočekávaný typ projeví hned
     * na hranici persistence, nikoliv až v obchodní logice.
     */
    private function mapCurrency(ActiveRow $row): Currency
    {
        $code = $row->code;
        $minorUnits = $row->minor_units;

        if (
            !is_string($code)
            || (!is_int($minorUnits) && (!is_string($minorUnits) || ctype_digit($minorUnits) === false))
        ) {
            throw new UnexpectedValueException('Databázový záznam měny obsahuje neplatný kód nebo počet desetinných míst.');
        }

        return new Currency($code, (int) $minorUnits);
    }
}
