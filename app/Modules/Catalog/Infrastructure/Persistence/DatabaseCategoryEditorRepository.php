<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\CategoryEditorRepository;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final class DatabaseCategoryEditorRepository implements CategoryEditorRepository
{
    public function __construct(private readonly Explorer $database) {}

    /**
     * @return array{parent_id: int|null, code: string, sort_order: int, is_active: bool}|null
     */
    public function findCategory(int $categoryId): ?array
    {
        $row = $this->database->table('category')->where('id', $categoryId)->fetch();
        if ($row === null) {
            return null;
        }

        $code = $row->code;
        if (!is_string($code)) {
            throw new UnexpectedValueException('Databázová kategorie obsahuje neplatný kód.');
        }

        return [
            'parent_id' => $this->mapNullableInteger($row->parent_id, 'category.parent_id'),
            'code' => $code,
            'sort_order' => $this->mapInteger($row->sort_order, 'category.sort_order'),
            'is_active' => $this->mapBoolean($row->is_active, 'category.is_active'),
        ];
    }

    /**
     * @return list<array{locale: string, name: string, description: string|null}>
     */
    public function findTranslations(int $categoryId): array
    {
        $rows = $this->database->table('category_translation')
            ->where('category_id', $categoryId)
            ->order('locale')
            ->fetchAll();

        $translations = [];
        foreach ($rows as $row) {
            $locale = $row->locale;
            $name = $row->name;
            if (!is_string($locale) || !is_string($name)) {
                throw new UnexpectedValueException('Překlad kategorie obsahuje neplatný jazyk nebo název.');
            }

            $translations[] = [
                'locale' => $locale,
                'name' => $name,
                'description' => $this->mapNullableString($row->description, 'category_translation.description'),
            ];
        }

        return $translations;
    }

    private function mapInteger(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A-?[0-9]+\z/D', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné PHP celé číslo.', $field));
    }

    private function mapNullableInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->mapInteger($value, $field);
    }

    private function mapBoolean(mixed $value, string $field): bool
    {
        if (in_array($value, [0, 1, '0', '1', false, true], true)) {
            return (bool) $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platný boolean.', $field));
    }

    private function mapNullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }
}
