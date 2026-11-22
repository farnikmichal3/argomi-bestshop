<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\CategoryEditorRepository;
use Nette\Database\Explorer;

final class DatabaseCategoryEditorRepository implements CategoryEditorRepository
{
    public function __construct(private readonly Explorer $database) {}

    public function findCategory(int $categoryId): ?array
    {
        $row = $this->database->table('category')->where('id', $categoryId)->fetch();
        if ($row === null) {
            return null;
        }

        return [
            'parent_id' => $row->parent_id === null ? null : (int) $row->parent_id,
            'code' => (string) $row->code,
            'sort_order' => (int) $row->sort_order,
            'is_active' => (bool) $row->is_active,
        ];
    }

    public function findTranslations(int $categoryId): array
    {
        $rows = $this->database->table('category_translation')
            ->where('category_id', $categoryId)
            ->order('locale')
            ->fetchAll();

        $translations = [];
        foreach ($rows as $row) {
            $translations[] = [
                'locale' => (string) $row->locale,
            'name' => (string) $row->name,
                'description' => $row->description === null ? null : (string) $row->description,
            ];
        }
        return $translations;
    }
}
