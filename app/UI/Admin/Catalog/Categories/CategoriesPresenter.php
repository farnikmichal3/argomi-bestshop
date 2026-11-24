<?php

declare(strict_types=1);

namespace App\UI\Admin\Catalog\Categories;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryDeletionBlockedException;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\CategoryWriter;
use App\Modules\Catalog\Api\CategoryEditorReader;
use App\Modules\Catalog\Api\CategoryOrderWriter;
use App\Modules\Catalog\Api\Dto\CategoryEditorData;
use App\Modules\Translation\Api\TranslationDomain;
use App\UI\Admin\AdminAccessTrait;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use JsonException;
use stdClass;

final class CategoriesPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?CategoryEditorData $editData = null;
    /** @var array<int|string, string> */
    private array $editParentOptions = [];

    public function __construct(
        private readonly CategoryTreeReader $categoryTree,
        private readonly CategoryWriter $categoryWriter,
        private readonly CategoryEditorReader $categoryEditorReader,
        private readonly CategoryOrderWriter $categoryOrderWriter,
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

        $groups = [];
        $tree = $this->categoryTree->findTree($this->localeProvider->getDefault());
        $this->appendSiblingGroups($tree, null, 0, $groups);

        $this->template->categoryGroups = $groups;
        $this->template->canManage = $this->catalogPermission->canManage();
        $this->template->parentOptions = $this->createParentOptions($tree);
        $this->template->locales = $this->localeProvider->getActiveLocales();
    }

    public function handleReorder(): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        if ($this->getHttpRequest()->getMethod() !== 'POST') {
            throw new ForbiddenRequestException();
        }

        $post = $this->getHttpRequest()->getPost();
        $parentId = $post['parentId'] ?? null;
        $orderedCategoryIds = $post['orderedCategoryIds'] ?? null;
        if (!is_string($parentId) || !is_string($orderedCategoryIds)) {
            $translator = $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $this->localeProvider->getDefault());
            $this->flashMessage($translator->translate('categories.order.error'), 'danger');
            $this->redirect('default');
        }

        $translator = $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $this->localeProvider->getDefault());
        $parsedParentId = null;
        if ($parentId !== '') {
            $validatedParentId = filter_var($parentId, FILTER_VALIDATE_INT);
            if (!is_int($validatedParentId) || $validatedParentId < 1) {
                $this->flashMessage($translator->translate('categories.order.error'), 'danger');
                $this->redirect('default');
            }
            $parsedParentId = $validatedParentId;
        }

        try {
            $categoryIds = json_decode($orderedCategoryIds, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($categoryIds) || !array_is_list($categoryIds)) {
                throw new InvalidArgumentException('Invalid category order payload.');
            }

            $this->categoryOrderWriter->reorder($parsedParentId, $categoryIds);
        } catch (JsonException|InvalidArgumentException) {
            $this->flashMessage($translator->translate('categories.order.error'), 'danger');
            $this->redirect('default');
        }

        $this->flashMessage($translator->translate('categories.order.saved'), 'success');
        $this->redirect('default');
    }

    public function actionEdit(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        $this->editData = $this->categoryEditorReader->findForEdit($id);
        if ($this->editData === null) {
            $this->redirect('default');
        }
        $tree = $this->categoryTree->findTree($this->localeProvider->getDefault());
        $excludedIds = $this->collectExcludedIds($tree, $id);
        $excludedIds[] = $id;
        $this->editParentOptions = $this->createParentOptions($tree, $excludedIds);
        $this->template->parentOptions = $this->editParentOptions;
        $this->template->category = $this->editData;
        $this->template->locales = $this->localeProvider->getActiveLocales();
    }

    protected function createComponentDeleteForm(): Form
    {
        if ($this->editData === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);
        $form->addHidden('categoryId')
            ->setDefaultValue((string) $this->editData->id);
        $form->addSubmit('delete', 'categories.delete.action')
            ->setHtmlAttribute('class', 'btn btn-outline-danger');

        $categoryId = $this->editData->id;
        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator, $categoryId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            if (!is_string($values->categoryId) || $values->categoryId !== (string) $categoryId) {
                $form->addError($translator->translate('categories.delete.not_found'));
                return;
            }

            try {
                $this->categoryWriter->delete($categoryId);
            } catch (CategoryDeletionBlockedException $exception) {
                $messageKey = match ($exception->reason) {
                    CategoryDeletionBlockedException::HAS_CHILDREN => 'categories.delete.has_children',
                    CategoryDeletionBlockedException::HAS_PRODUCTS => 'categories.delete.has_products',
                    default => 'categories.delete.not_found',
                };
                $form->addError($translator->translate($messageKey));
                return;
            }

            $this->flashMessage($translator->translate('categories.delete.deleted'), 'success');
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentEditForm(): Form
    {
        if ($this->editData === null) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $this->localeProvider->getDefault());
        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('parentId', 'categories.form.parent', $this->editParentOptions)->setHtmlAttribute('class', 'form-select')->setPrompt('categories.form.parent.none');
        $form->addText('code', 'categories.form.code')->setHtmlAttribute('class', 'form-control')->setRequired('categories.form.error.required');
        $form->addInteger('sortOrder', 'categories.form.sort_order')->setHtmlAttribute('class', 'form-control')->addRule(Form::Min, 'categories.form.error.sort_order', 0);
        $form->addCheckbox('isActive', 'categories.form.active');
        $translations = $form->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $container = $translations->addContainer($code);
            $container->addText('name', 'categories.form.name')->setHtmlAttribute('class', 'form-control')->setRequired('categories.form.error.required');
            $container->addTextArea('description', 'categories.form.description')->setHtmlAttribute('class', 'form-control')->setHtmlAttribute('rows', 3);
        }
        $defaults = [
            'code' => $this->editData->code,
            'sortOrder' => $this->editData->sortOrder,
            'isActive' => $this->editData->isActive,
            'translations' => $this->editData->translations,
        ];
        if ($this->editData->parentId !== null) {
            $defaults['parentId'] = (string) $this->editData->parentId;
        }
        $form->setDefaults($defaults);
        $form->addSubmit('submit', 'categories.form.edit.submit')->setHtmlAttribute('class', 'btn btn-primary');
        $editCategoryId = $this->editData->id;
        $form->onSuccess[] = function (Form $form, array $values) use ($translator, $editCategoryId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            $parentId = null;
            if (is_string($values['parentId']) && $values['parentId'] !== '') {
                $parsed = filter_var($values['parentId'], FILTER_VALIDATE_INT);
                if (!is_int($parsed) || $parsed < 1) {
                    $form->addError($translator->translate('categories.form.error.invalid'));
                    return;
                }
                $parentId = $parsed;
            }
            if (!is_string($values['code']) || !is_int($values['sortOrder']) || !is_bool($values['isActive']) || !is_array($values['translations'])) {
                $form->addError($translator->translate('categories.form.error.invalid'));
                return;
            }
            $translations = [];
            foreach ($values['translations'] as $locale => $translation) {
                if (!is_string($locale) || !is_array($translation)) {
                    $form->addError($translator->translate('categories.form.error.invalid'));
                    return;
                }
                $translations[$locale] = $translation;
            }
            try {
                $this->categoryWriter->update($editCategoryId, $parentId, $values['code'], $values['sortOrder'], $values['isActive'], $translations);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('categories.form.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('categories.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('categories.form.updated'), 'success');
            $this->redirect('default');
        };
        return $form;
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
            ->addRule(Form::Min, 'categories.form.error.sort_order', 0);
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

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $parentValue = $values['parentId'];
            $parentId = null;
            if (is_string($parentValue) && $parentValue !== '') {
                $parsedParentId = filter_var($parentValue, FILTER_VALIDATE_INT);
                if (!is_int($parsedParentId) || $parsedParentId < 1) {
                    $form->addError($translator->translate('categories.form.error.invalid'));
                    return;
                }
                $parentId = $parsedParentId;
            }

            $code = $values['code'];
            $sortOrder = $values['sortOrder'];
            $active = $values['isActive'];
            if (!is_string($code) || !is_int($sortOrder) || !is_bool($active) || !is_array($values['translations'])) {
                $form->addError($translator->translate('categories.form.error.invalid'));
                return;
            }

            $translations = [];
            foreach ($values['translations'] as $locale => $translation) {
                if (!is_string($locale) || !is_array($translation)) {
                    $form->addError($translator->translate('categories.form.error.invalid'));
                    return;
                }
                $translations[$locale] = $translation;
            }

            try {
                $this->categoryWriter->create($parentId, $code, $sortOrder, $active, $translations);
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
     * @param list<array{parentId: ?int, parentCode: ?string, formId: string, orderedIds: string, rows: list<array{category: CategoryTreeItem, depth: int, prefix: string}>}> $groups
     */
    private function appendSiblingGroups(array $nodes, ?int $parentId, int $depth, array &$groups, ?string $parentCode = null): void
    {
        if ($nodes === []) {
            return;
        }

        $rows = [];
        $ids = [];
        foreach ($nodes as $node) {
            $rows[] = [
                'category' => $node,
                'depth' => $depth,
                'prefix' => str_repeat('— ', $depth),
            ];
            $ids[] = $node->id;
        }

        $groups[] = [
            'parentId' => $parentId,
            'parentCode' => $parentCode,
            'formId' => $parentId === null ? 'category-order-root' : 'category-order-' . $parentId,
            'orderedIds' => json_encode($ids, JSON_THROW_ON_ERROR),
            'rows' => $rows,
        ];

        foreach ($nodes as $node) {
            $this->appendSiblingGroups($node->children, $node->id, $depth + 1, $groups, $node->code);
        }
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param list<int> $excludedIds
     * @return array<int|string, string>
     */
    private function createParentOptions(array $nodes, array $excludedIds = []): array
    {
        $options = [];
        $this->appendParentOptions($nodes, 0, $options, $excludedIds);
        return $options;
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param array<int|string, string> $options
     * @param list<int> $excludedIds
     */
    private function appendParentOptions(array $nodes, int $depth, array &$options, array $excludedIds = []): void
    {
        foreach ($nodes as $node) {
            if (!in_array($node->id, $excludedIds, true)) {
                $options[(string) $node->id] = str_repeat('— ', $depth) . $node->code;
            }
            $this->appendParentOptions($node->children, $depth + 1, $options, $excludedIds);
        }
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @return list<int>
     */
    private function collectExcludedIds(array $nodes, int $categoryId): array
    {
        foreach ($nodes as $node) {
            if ($node->id === $categoryId) {
                return $this->collectTreeIds($node->children);
            }
            $found = $this->collectExcludedIds($node->children, $categoryId);
            if ($found !== []) {
                return $found;
            }
        }
        return [];
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @return list<int>
     */
    private function collectTreeIds(array $nodes): array
    {
        $ids = [];
        foreach ($nodes as $node) {
            $ids[] = $node->id;
            array_push($ids, ...$this->collectTreeIds($node->children));
        }
        return $ids;
    }

    /**
     * @return array<int|string, string>
     */
    private function getParentOptions(): array
    {
        return $this->createParentOptions(
            $this->categoryTree->findTree($this->localeProvider->getDefault()),
        );
    }
}
