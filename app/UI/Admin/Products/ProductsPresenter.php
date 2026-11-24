<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\Dto\ProductEditorData;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Application\ProductWriteService;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Modules\Tax\Api\TaxClassOptionReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\UI\Admin\AdminAccessTrait;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use stdClass;

final class ProductsPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?ProductEditorData $editData = null;

    private bool $creating = false;

    /** @var array<int|string, string> */
    private array $categoryOptions = [];

    /** @var array<int|string, string> */
    private array $taxClassOptions = [];

    public function __construct(
        private readonly ProductAdminDataGridFactory $productAdminDataGridFactory,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
        private readonly ProductEditorReader $productEditorReader,
        private readonly TaxClassOptionReader $taxClassOptionReader,
        private readonly ProductWriter $productWriter,
        private readonly CategoryTreeReader $categoryTreeReader,
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

        $this->template->canManage = $this->catalogPermission->canManage();
    }

    public function actionCreate(): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $this->creating = true;
        $this->loadEditorOptions();
        $this->template->isCreate = true;
    }

    public function actionEdit(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $this->editData = $this->productEditorReader->findForEdit($id);
        if ($this->editData === null) {
            $this->flashMessage(
                $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $this->localeProvider->getDefault())
                    ->translate('products.editor.not_found'),
                'warning',
            );
            $this->redirect('default');
        }

        $this->loadEditorOptions();
        $this->template->isCreate = false;
        $this->template->product = $this->editData;
    }

    protected function createComponentProductsGrid(string $name): Control
    {
        if (!$this->catalogPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        return $this->productAdminDataGridFactory->create(
            $this,
            $name,
            $this->resolveLocale(),
            $this->catalogPermission->canManage(),
        );
    }

    protected function createComponentProductForm(): Form
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addText('code', 'products.editor.code')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('products.editor.error.required');

        $statusOptions = [];
        foreach (ProductAdminStatus::cases() as $status) {
            $statusOptions[$status->value] = 'products.admin.status.' . $status->value;
        }
        $form->addSelect('status', 'products.editor.status', $statusOptions)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired();

        $typeOptions = [];
        foreach (ProductAdminType::cases() as $type) {
            $typeOptions[$type->value] = 'products.admin.type.' . $type->value;
        }
        $form->addSelect('type', 'products.editor.type', $typeOptions)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired();

        $form->addSelect('taxClassId', 'products.editor.tax_class', $this->taxClassOptions)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired();

        $form->addMultiSelect('categoryIds', 'products.editor.categories', $this->categoryOptions)
            ->setHtmlAttribute('class', 'form-select')
            ->setHtmlAttribute('size', '8');

        $translations = $form->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $container = $translations->addContainer($code);
            $container->addText('name', 'products.editor.name')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('products.editor.error.required');
            $container->addTextArea('shortDescription', 'products.editor.short_description')
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('rows', '3');
            $container->addTextArea('description', 'products.editor.description')
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('rows', '6');
            $container->addText('metaTitle', 'products.editor.meta_title')
                ->setHtmlAttribute('class', 'form-control');
            $container->addTextArea('metaDescription', 'products.editor.meta_description')
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('rows', '2');
        }

        $defaults = [
            'status' => ProductAdminStatus::Draft->value,
            'type' => ProductAdminType::Physical->value,
            'categoryIds' => [],
        ];
        if ($this->editData !== null) {
            $defaults = [
                'code' => $this->editData->code,
                'status' => $this->editData->status,
                'type' => $this->editData->type,
                'taxClassId' => (string) $this->editData->taxClassId,
                'categoryIds' => array_map('strval', $this->editData->categoryIds),
                'translations' => $this->editData->translations,
            ];
        } elseif ($this->taxClassOptions !== []) {
            $defaults['taxClassId'] = (string) array_key_first($this->taxClassOptions);
        }
        $form->setDefaults($defaults);

        $form->addSubmit('submit', 'products.editor.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            if (
                !is_string($values->code)
                || !is_string($values->status)
                || !is_string($values->type)
                || !is_string($values->taxClassId)
                || !is_array($values->translations)
                || !is_array($values->categoryIds)
            ) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $status = ProductAdminStatus::tryFrom($values->status);
            $type = ProductAdminType::tryFrom($values->type);
            $taxClassId = filter_var($values->taxClassId, FILTER_VALIDATE_INT);
            if ($status === null || $type === null || !is_int($taxClassId) || $taxClassId < 1) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $categoryIds = [];
            foreach ($values->categoryIds as $categoryId) {
                if (!is_string($categoryId) && !is_int($categoryId)) {
                    $form->addError($translator->translate('products.editor.error.invalid'));
                    return;
                }
                $parsed = filter_var((string) $categoryId, FILTER_VALIDATE_INT);
                if (!is_int($parsed) || $parsed < 1) {
                    $form->addError($translator->translate('products.editor.error.invalid'));
                    return;
                }
                $categoryIds[] = $parsed;
            }

            $translations = [];
            foreach ($values->translations as $locale => $translation) {
                if (!is_string($locale) || !is_array($translation)) {
                    $form->addError($translator->translate('products.editor.error.invalid'));
                    return;
                }
                $translations[$locale] = $translation;
            }

            try {
                if ($this->creating) {
                    $this->productWriter->create(
                        $values->code,
                        $status,
                        $type,
                        $taxClassId,
                        $translations,
                        $categoryIds,
                    );
                } elseif ($this->editData !== null) {
                    $this->productWriter->update(
                        $this->editData->id,
                        $values->code,
                        $status,
                        $type,
                        $taxClassId,
                        $translations,
                        $categoryIds,
                    );
                } else {
                    throw new InvalidArgumentException('Editor produktu není inicializován.');
                }
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('products.editor.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $this->flashMessage(
                $translator->translate($this->creating ? 'products.editor.created' : 'products.editor.updated'),
                'success',
            );
            $this->redirect('default');
        };

        return $form;
    }

    private function loadEditorOptions(): void
    {
        $this->categoryOptions = [];
        $this->appendCategoryOptions($this->categoryTreeReader->findTree($this->localeProvider->getDefault()), 0);
        $this->taxClassOptions = [];
        foreach ($this->taxClassOptionReader->findOptions() as $taxClass) {
            $this->taxClassOptions[$taxClass->id] = $taxClass->name . ' (' . $taxClass->code . ')';
        }
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     */
    private function appendCategoryOptions(array $nodes, int $depth): void
    {
        foreach ($nodes as $node) {
            $this->categoryOptions[$node->id] = str_repeat('— ', $depth) . ($node->name ?? $node->code);
            $this->appendCategoryOptions($node->children, $depth + 1);
        }
    }

    private function resolveLocale(): \App\Shared\Domain\Locale\Locale
    {
        $language = $this->getParameter('lang');

        if ($language === null) {
            return $this->localeProvider->getDefault();
        }

        if (!is_string($language)) {
            throw new BadRequestException('Neplatný jazyk požadavku.');
        }

        try {
            $locale = \App\Shared\Domain\Locale\Locale::fromString($language);
        } catch (\App\Shared\Domain\Locale\InvalidLocaleException $exception) {
            throw new BadRequestException('Neplatný jazyk požadavku.', previous: $exception);
        }

        if (!$this->localeProvider->isActive($locale)) {
            throw new BadRequestException('Požadovaný jazyk není aktivní.');
        }

        return $locale;
    }
}
