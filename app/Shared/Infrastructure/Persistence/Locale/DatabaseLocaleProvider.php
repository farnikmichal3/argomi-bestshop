<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Locale;

use App\Shared\Domain\Locale\DefaultLocaleNotFoundException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Načítá výchozí a aktivní jazyky z tabulky language.
 *
 * Databázové řádky se převádějí na Locale uvnitř persistence vrstvy;
 * ActiveRow proto nikdy neopouští infrastrukturu.
 */
final class DatabaseLocaleProvider implements LocaleProvider
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    /**
     * @throws DefaultLocaleNotFoundException Pokud chybí výchozí aktivní jazyk.
     * @throws UnexpectedValueException Pokud databázový kód nelze převést na Locale.
     */
    public function getDefault(): Locale
    {
        $row = $this->database->table('language')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->fetch();

        if ($row === null) {
            throw new DefaultLocaleNotFoundException();
        }

        return $this->mapLocale($row);
    }

    /**
     * @return list<Locale>
     * @throws UnexpectedValueException Pokud databázový kód nelze převést na Locale.
     */
    public function getActiveLocales(): array
    {
        $rows = $this->database->table('language')
            ->where('is_active', 1)
            ->order('sort_order ASC, code ASC')
            ->fetchAll();

        $locales = [];

        foreach ($rows as $row) {
            $locales[] = $this->mapLocale($row);
        }

        return $locales;
    }

    public function isActive(Locale $locale): bool
    {
        return $this->database->table('language')
            ->where('code', $locale->getCode())
            ->where('is_active', 1)
            ->count() > 0;
    }

    /**
     * Ověření při mapování chrání aplikační vrstvu i při ruční změně databáze.
     *
     * @throws UnexpectedValueException Pokud je kód v databázi neplatný.
     */
    private function mapLocale(ActiveRow $row): Locale
    {
        $code = $row->code;

        if (!is_string($code)) {
            throw new UnexpectedValueException('Databázový záznam jazyka neobsahuje textový kód.');
        }

        try {
            return Locale::fromString($code);
        } catch (\App\Shared\Domain\Locale\InvalidLocaleException $exception) {
            throw new UnexpectedValueException('Databázový záznam jazyka obsahuje neplatný kód.', previous: $exception);
        }
    }
}
