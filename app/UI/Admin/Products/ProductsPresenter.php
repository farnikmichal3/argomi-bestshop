<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\Dto\ProductEditorData;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Api\ProductEditorConfigurationReader;
use App\Modules\Catalog\Api\ProductConfigurationWriter;
use App\Modules\Catalog\Api\AttributeDataType;
use App\Modules\Catalog\Api\Dto\ProductEditorConfigurationData;
use App\Modules\Catalog\Api\Dto\ProductEditorAttributeOption;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Modules\Catalog\Api\ProductWriter;
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

final class ProductsPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?ProductEditorData $editData = null;

    private bool $creating = false;

    private ?ProductEditorConfigurationData $configurationData = null;

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
        private readonly ProductEditorConfigurationReader $productEditorConfigurationReader,
        private readonly ProductConfigurationWriter $productConfigurationWriter,
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
        $this->configurationData = $this->productEditorConfigurationReader->findForProduct($id, $this->resolveLocale());
        if ($this->configurationData === null) {
            throw new BadRequestException('Konfigurace produktu nebyla nalezena.');
        }
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
                ->setHtmlAttribute('rows', '12')
                ->setHtmlAttribute('data-hugerte', 'product-description');
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

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            if (
                !is_string($values['code'])
                || !is_string($values['status'])
                || !is_string($values['type'])
                || (!is_string($values['taxClassId']) && !is_int($values['taxClassId']))
                || !is_array($values['translations'])
                || !is_array($values['categoryIds'])
            ) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $status = ProductAdminStatus::tryFrom($values['status']);
            $type = ProductAdminType::tryFrom($values['type']);
            $taxClassId = filter_var((string) $values['taxClassId'], FILTER_VALIDATE_INT);
            if ($status === null || $type === null || !is_int($taxClassId) || $taxClassId < 1) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $categoryIds = [];
            foreach ($values['categoryIds'] as $categoryId) {
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
            foreach ($values['translations'] as $locale => $translation) {
                if (!is_string($locale) || !is_array($translation)) {
                    $form->addError($translator->translate('products.editor.error.invalid'));
                    return;
                }
                $translations[$locale] = $translation;
            }

            try {
                if ($this->creating) {
                    $productId = $this->productWriter->create(
                        $values['code'],
                        $status,
                        $type,
                        $taxClassId,
                        $translations,
                        $categoryIds,
                    );
                } elseif ($this->editData !== null) {
                    $this->productWriter->update(
                        $this->editData->id,
                        $values['code'],
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
            if ($this->creating) {
                $this->redirect('edit', $productId);
            }
            $this->redirect('edit', $this->editData?->id ?? 0);
        };

        return $form;
    }

    protected function createComponentProductConfigurationForm(): Form
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        if ($this->editData === null || $this->configurationData === null) {
            throw new BadRequestException('Konfigurace produktu není inicializována.');
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);

        $defaults = [];
        $axisOrder = array_flip($this->configurationData->variantAxisIds);
        foreach ($this->configurationData->availableAttributes as $attribute) {
            $fieldName = 'attribute_' . $attribute->id;
            $assigned = $this->configurationData->assignedValues[$attribute->id] ?? null;

            if ($attribute->type === AttributeDataType::Enum) {
                $options = [];
                foreach ($attribute->values as $value) {
                    $options[(string) $value->id] = $value->label;
                }
                $form->addSelect($fieldName, $attribute->name, $options)
                    ->setPrompt('products.configuration.value.prompt')
                    ->setHtmlAttribute('class', 'form-select');
                if (is_int($assigned)) {
                    $defaults[$fieldName] = (string) $assigned;
                }
            } elseif ($attribute->type === AttributeDataType::Bool) {
                $form->addSelect($fieldName, $attribute->name, [
                    '0' => 'products.configuration.boolean.no',
                    '1' => 'products.configuration.boolean.yes',
                ])
                    ->setPrompt('products.configuration.value.prompt')
                    ->setHtmlAttribute('class', 'form-select');
                if (is_bool($assigned)) {
                    $defaults[$fieldName] = $assigned ? '1' : '0';
                }
            } else {
                $form->addText($fieldName, $attribute->name)
                    ->setHtmlAttribute('class', 'form-control');
                if (is_string($assigned)) {
                    $defaults[$fieldName] = $assigned;
                }
            }

            $axisField = 'axis_' . $attribute->id;
            $orderField = 'axis_order_' . $attribute->id;
            $form->addCheckbox($axisField, $attribute->name . ' — ' . $translator->translate('products.configuration.axis'))
                ->setHtmlAttribute('class', 'form-check-input');
            $form->addInteger($orderField, 'products.configuration.axis_order')
                ->setHtmlAttribute('class', 'form-control form-control-sm')
                ->setHtmlAttribute('min', '1');
            if (array_key_exists($attribute->id, $axisOrder)) {
                $defaults[$axisField] = true;
                $defaults[$orderField] = (string) ($axisOrder[$attribute->id] + 1);
            }
        }

        $form->setDefaults($defaults);
        $form->addSubmit('submit', 'products.configuration.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null || $this->configurationData === null) {
                throw new ForbiddenRequestException();
            }

            $attributes = [];
            foreach ($this->configurationData->availableAttributes as $attribute) {
                $fieldName = 'attribute_' . $attribute->id;
                $rawValue = $values[$fieldName] ?? null;
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }

                if ($attribute->type === AttributeDataType::Enum) {
                    $parsed = filter_var((string) $rawValue, FILTER_VALIDATE_INT);
                    if (!is_int($parsed) || $parsed < 1) {
                        $form->addError($translator->translate('products.editor.error.invalid'));
                        return;
                    }
                    $value = $parsed;
                } elseif ($attribute->type === AttributeDataType::Bool) {
                    if ($rawValue !== '0' && $rawValue !== '1' && $rawValue !== 0 && $rawValue !== 1) {
                        $form->addError($translator->translate('products.editor.error.invalid'));
                        return;
                    }
                    $value = (string) $rawValue === '1';
                } else {
                    if (!is_string($rawValue)) {
                        $form->addError($translator->translate('products.editor.error.invalid'));
                        return;
                    }
                    $value = trim($rawValue);
                    if ($value === '') {
                        continue;
                    }
                }

                $attributes[] = ['attributeId' => $attribute->id, 'value' => $value];
            }

            $orderedAxes = [];
            foreach ($this->configurationData->availableAttributes as $attribute) {
                if (($values['axis_' . $attribute->id] ?? false) !== true) {
                    continue;
                }
                $order = filter_var($values['axis_order_' . $attribute->id] ?? null, FILTER_VALIDATE_INT);
                if (!is_int($order) || $order < 1) {
                    $form->addError($translator->translate('products.editor.error.invalid'));
                    return;
                }
                $orderedAxes[] = ['id' => $attribute->id, 'order' => $order];
            }
            usort($orderedAxes, static fn (array $left, array $right): int => $left['order'] <=> $right['order'] ?: $left['id'] <=> $right['id']);
            $orders = array_column($orderedAxes, 'order');
            if (count($orders) !== count(array_unique($orders))) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }
            $axisIds = array_column($orderedAxes, 'id');

            try {
                $this->productConfigurationWriter->save($this->editData->id, $attributes, $axisIds);
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.configuration.saved'), 'success');
            $this->redirect('edit', $this->editData->id);
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
