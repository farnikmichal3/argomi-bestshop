<?php

declare(strict_types=1);

namespace App\UI\Admin\Catalog;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\CategoryWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use stdClass;

final class CategoriesPresenter extends TranslatedPresenter
{
    public function __construct(
        private readonly CategoryTreeReader $categoryTree,
        private readonly CategoryWriter $categoryWriter,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
        private readonly TranslatorFactory $translatorFactory,
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
        $tree = $this->categoryTree->findTree($this->localeProvider->getDefault());
        $this->appendRows($tree, 0, $rows);

        $this->template->categories = $rows;
        $this->template->canManage = $this->catalogPermission->canManage();
        $this->template->parentOptions = $this->createParentOptions($tree);
        $this->template->locales = $this->localeProvider->getActiveLocales();
    }

    protected function createComponentCreateForm(): Form
    {
        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('parentId', 'categories.form.parent', $this->getParentOptions())
            ->setHtmlAttribute('class', 'form-select')
            ->setPrompt('categories.form.parent.none');
        $form->addText('code', 'categories.form.code')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('categories.form.error.required');
        $form->addInteger('sortOrder', 'categories.form.sort_order')
            ->setHtmlAttribute('class', 'form-control')
            ->setDefaultValue(0)
            ->addRule(Form::MIN, 'categories.form.error.sort_order', 0);
        $form->addCheckbox('isActive', 'categories.form.active')
            ->setDefaultValue(true);

        $translations = $form->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $container = $translations->addContainer($code);
            $container->addText('name', 'categories.form.name')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('categories.form.error.required');
            $container->addTextArea('description', 'categories.form.description')
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('rows', 3);
        }

        $form->addSubmit('submit', 'categories.form.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $parentValue = $values->parentId;
            $parentId = null;
            if (is_string($parentValue) && $parentValue !== '') {
                $parsedParentId = filter_var($parentValue, FILTER_VALIDATE_INT);
                if (!is_int($parsedParentId) || $parsedParentId < 1) {
                    $form->addError($translator->translate('categories.form.error.invalid'));
                    return;
                }
                $parentId = $parsedParentId;
            }

            $code = $values->code;
            $sortOrder = $values->sortOrder;
            $active = $values->isActive;
            if (!is_string($code) || !is_int($sortOrder) || !is_bool($active) || !is_array($values->translations)) {
                $form->addError($translator->translate('categories.form.error.invalid'));
                return;
            }

            try {
                $this->categoryWriter->create($parentId, $code, $sortOrder, $active, $values->translations);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('categories.form.error.duplicate'));
                return;
            } catch (\InvalidArgumentException) {
                $form->addError($translator->translate('categories.form.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('categories.form.created'), 'success');
            $this->redirect('default');
        };

        return $form;
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

    /**
     * @param list<CategoryTreeItem> $nodes
     * @return array<string, string>
     */
    private function createParentOptions(array $nodes): array
    {
        $options = [];
        $this->appendParentOptions($nodes, 0, $options);
        return $options;
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param array<string, string> $options
     */
    private function appendParentOptions(array $nodes, int $depth, array &$options): void
    {
        foreach ($nodes as $node) {
            $options[(string) $node->id] = str_repeat('— ', $depth) . $node->code;
            $this->appendParentOptions($node->children, $depth + 1, $options);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getParentOptions(): array
    {
        return $this->createParentOptions(
            $this->categoryTree->findTree($this->localeProvider->getDefault()),
        );
    }
}
