<?php

declare(strict_types=1);

namespace App\UI\Admin\Catalog;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\ForbiddenRequestException;

final class CategoriesPresenter extends TranslatedPresenter
{
    public function __construct(
        private readonly CategoryTreeReader $categoryTree,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
    ) {
        parent::__construct();
    }

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Catalog;
    }

    public function renderDefault(): void
    {
        if (!$this->catalogPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $rows = [];
        $this->appendRows(
            $this->categoryTree->findTree($this->localeProvider->getDefault()),
            0,
            $rows,
        );

        $this->template->categories = $rows;
        $this->template->canManage = $this->catalogPermission->canManage();
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param list<array{category: CategoryTreeItem, depth: int, prefix: string}> $rows
     */
    private function appendRows(array $nodes, int $depth, array &$rows): void
    {
        foreach ($nodes as $node) {
            $rows[] = ['category' => $node, 'depth' => $depth, 'prefix' => str_repeat('— ', $depth)];
            $this->appendRows($node->children, $depth + 1, $rows);
        }
    }
}
