<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Persistence;

use App\Modules\Translation\Api\DefaultLanguageCannotBeDeactivatedException;
use App\Modules\Translation\Api\Dto\ManagedLocale;
use App\Modules\Translation\Api\LanguageNotFoundException;
use App\Modules\Translation\Api\LocaleManager;
use App\Shared\Domain\Locale\Locale;
use InvalidArgumentException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Spravuje jazykový katalog pomocí Nette Database Exploreru.
 */
final class DatabaseLocaleManager implements LocaleManager
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function getAll(): array
    {
        $languages = [];

        foreach ($this->database->table('language')->order('sort_order ASC, code ASC')->fetchAll() as $row) {
            $languages[] = $this->map($row);
        }

        return $languages;
    }

    public function add(Locale $locale, string $name, int $sortOrder = 0): void
    {
        if (preg_match('//u', $name) !== 1) {
            throw new InvalidArgumentException('Název jazyka musí být platný UTF-8 text.');
        }

        $nameLength = preg_match_all('/./us', $name);

        if (trim($name) === '' || $nameLength > 100 || $sortOrder < 0) {
            throw new InvalidArgumentException('Jazyk musí mít název a nezáporné pořadí.');
        }

        $this->database->table('language')->insert([
            'code' => $locale->getCode(),
            'name' => $name,
            'is_default' => 0,
            'is_active' => 0,
            'sort_order' => $sortOrder,
        ]);
    }

    public function setActive(Locale $locale, bool $active): void
    {
        $selection = $this->database->table('language')
            ->where('code', $locale->getCode());

        $row = $selection->fetch();

        if ($row === null) {
            throw new LanguageNotFoundException();
        }

        if (!$active && $this->toBoolean($row->is_default)) {
            throw new DefaultLanguageCannotBeDeactivatedException();
        }

        $selection->update([
            'is_active' => $active ? 1 : 0,
        ]);
    }

    private function map(ActiveRow $row): ManagedLocale
    {
        $code = $row->code;
        $name = $row->name;
        $sortOrder = $row->sort_order;

        if (!is_string($code) || !is_string($name) || !is_int($sortOrder)) {
            throw new UnexpectedValueException('Databázový jazyk obsahuje neplatné hodnoty.');
        }

        return new ManagedLocale(
            $code,
            $name,
            $this->toBoolean($row->is_active),
            $this->toBoolean($row->is_default),
            $sortOrder,
        );
    }

    private function toBoolean(mixed $value): bool
    {
        if (!in_array($value, [0, 1, '0', '1', false, true], true)) {
            throw new UnexpectedValueException('Databázový příznak jazyka není boolean.');
        }

        return (bool) $value;
    }
}
