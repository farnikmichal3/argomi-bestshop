<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\CategoryWriteRepository;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final class DatabaseCategoryWriteRepository implements CategoryWriteRepository
{
    public function __construct(private readonly Explorer $database) {}

    public function isCodeAvailable(string $code, ?int $excludeCategoryId = null): bool
    {
        $selection = $this->database->table('category')->where('code', $code);
        if ($excludeCategoryId !== null) {
            $selection->where('id != ?', $excludeCategoryId);
        }
        return $selection->fetch() === null;
    }

    public function parentExists(int $categoryId): bool
    {
        return $this->database->table('category')->where('id', $categoryId)->fetch() !== null;
    }

    public function findParentId(int $categoryId): ?int
    {
        $row = $this->database->table('category')->where('id', $categoryId)->fetch();
        if ($row === null || $row->parent_id === null) {
            return null;
        }

        $value = $row->parent_id;
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if (is_int($integer)) {
                return $integer;
            }
        }
        throw new UnexpectedValueException('Databázový rodič kategorie není platné celé číslo.');
    }

    public function createCategory(?int $parentId, string $code, int $sortOrder, bool $isActive): int
    {
        $inserted = $this->database->table('category')->insert([
            'parent_id' => $parentId,
            'code' => $code,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
        if (!$inserted instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nevrátila řádek nové kategorie.');
        }
        $id = $inserted->id;
        if (is_int($id)) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id)) {
            $integer = filter_var($id, FILTER_VALIDATE_INT);
            if (is_int($integer)) {
                return $integer;
            }
        }
        throw new UnexpectedValueException('Databáze nevrátila platné ID nové kategorie.');
    }

    public function updateCategory(int $categoryId, ?int $parentId, string $code, int $sortOrder, bool $isActive): void
    {
        $this->database->table('category')->where('id', $categoryId)->update([
            'parent_id' => $parentId,
            'code' => $code,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
    }

    public function saveTranslation(int $categoryId, Locale $locale, string $name, string $slug, ?string $description): void
    {
        $selection = $this->database->table('category_translation')
            ->where('category_id', $categoryId)
            ->where('locale', $locale->getCode());
        $existing = $selection->fetch();
        $data = ['name' => $name, 'slug' => $slug, 'description' => $description];
        if ($existing === null) {
            $this->database->table('category_translation')->insert([
                'category_id' => $categoryId,
                'locale' => $locale->getCode(),
                ...$data,
            ]);
            return;
        }
        $selection->update($data);
    }

    public function findSiblingIds(?int $parentId): array
    {
        $selection = $this->database->table('category');
        if ($parentId === null) {
            $selection->where('parent_id IS NULL');
        } else {
            $selection->where('parent_id', $parentId);
        }

        $ids = [];
        foreach ($selection->order('sort_order ASC, id ASC') as $row) {
            $value = $row->id;
            if (is_int($value)) {
                $ids[] = $value;
                continue;
            }
            if (is_string($value) && ctype_digit($value)) {
                $integer = filter_var($value, FILTER_VALIDATE_INT);
                if (is_int($integer)) {
                    $ids[] = $integer;
                    continue;
                }
            }
            throw new UnexpectedValueException('Databáze vrátila neplatné ID kategorie.');
        }

        return $ids;
    }

    public function updateSortOrder(int $categoryId, int $sortOrder): void
    {
        $this->database->table('category')->where('id', $categoryId)->update([
            'sort_order' => $sortOrder,
        ]);
    }
}
