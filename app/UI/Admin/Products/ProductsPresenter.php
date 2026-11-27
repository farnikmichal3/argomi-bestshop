<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\Dto\ProductEditorData;
use App\Modules\Catalog\Api\ProductEditorReader;
use App\Modules\Catalog\Api\Dto\ProductImageData;
use App\Modules\Catalog\Api\ProductImageReader;
use App\Modules\Catalog\Api\ProductImageWriter;
use App\Modules\Catalog\Api\ProductRelationKind;
use App\Modules\Catalog\Api\ProductRelationReader;
use App\Modules\Catalog\Api\ProductRelationWriter;
use App\Modules\Catalog\Api\Dto\ProductRelationItem;
use App\Modules\Catalog\Api\Dto\ProductRelationOrder;
use App\Modules\Catalog\Api\ProductEditorConfigurationReader;
use App\Modules\Catalog\Api\ProductConfigurationWriter;
use App\Modules\Catalog\Api\ProductCustomerOptionReader;
use App\Modules\Catalog\Api\ProductCustomerOptionWriter;
use App\Modules\Catalog\Api\Dto\ProductCustomerOptionConfigurationData;
use App\Modules\Catalog\Api\AttributeDataType;
use App\Modules\Catalog\Api\Dto\ProductEditorConfigurationData;
use App\Modules\Catalog\Api\ProductActivationRequirementsNotMet;
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

    private ?ProductEditorData $lifecycleProduct = null;

    private bool $creating = false;

    private ?ProductEditorConfigurationData $configurationData = null;

    private ?ProductCustomerOptionConfigurationData $customerOptionData = null;

    /** @var list<ProductImageData> */
    private array $productImages = [];

    /** @var list<ProductRelationItem> */
    private array $productRelations = [];

    /** @var array<int|string, string> */
    private array $productRelationOptions = [];

    /** @var array<int|string, string> */
    private array $categoryOptions = [];

    /** @var array<int|string, string> */
    private array $taxClassOptions = [];

    public function __construct(
        private readonly ProductAdminDataGridFactory $productAdminDataGridFactory,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
        private readonly ProductEditorReader $productEditorReader,
        private readonly ProductImageReader $productImageReader,
        private readonly ProductImageWriter $productImageWriter,
        private readonly ProductRelationReader $productRelationReader,
        private readonly ProductRelationWriter $productRelationWriter,
        private readonly TaxClassOptionReader $taxClassOptionReader,
        private readonly ProductWriter $productWriter,
        private readonly ProductLifecycleWriter $productLifecycleWriter,
        private readonly ProductEditorConfigurationReader $productEditorConfigurationReader,
        private readonly ProductConfigurationWriter $productConfigurationWriter,
        private readonly ProductCustomerOptionReader $productCustomerOptionReader,
        private readonly ProductCustomerOptionWriter $productCustomerOptionWriter,
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
        $this->customerOptionData = $this->productCustomerOptionReader->findForProduct($id);
        if ($this->customerOptionData === null) {
            throw new BadRequestException('Konfigurace zákaznických voleb nebyla nalezena.');
        }
        $this->template->customerOptionData = $this->customerOptionData;
        $this->productImages = $this->productImageReader->findForProduct($id);
        $this->template->productImages = $this->productImages;
        $this->productRelations = $this->productRelationReader->findForProduct($id, $this->resolveLocale());
        $this->template->productRelations = $this->productRelations;
        $this->productRelationOptions = [];
        foreach ($this->productRelationReader->findAvailableProducts($id, $this->resolveLocale()) as $option) {
            $this->productRelationOptions[$option->id] = ($option->name ?? $option->code) . ' (' . $option->code . ')';
        }
        $this->template->productRelationOptions = $this->productRelationOptions;
        $this->template->isCreate = false;
        $this->template->product = $this->editData;
    }

    public function actionArchive(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $this->lifecycleProduct = $this->productEditorReader->findForEdit($id);
        if ($this->lifecycleProduct === null) {
            $this->flashMessage(
                $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $this->localeProvider->getDefault())
                    ->translate('products.lifecycle.error.not_found'),
                'warning',
            );
            $this->redirect('default');
        }

        $localeCode = $this->resolveLocale()->getCode();
        $this->template->product = $this->lifecycleProduct;
        $this->template->productName = $this->lifecycleProduct->translations[$localeCode]['name'] ?? $this->lifecycleProduct->code;
        $this->template->isArchived = $this->lifecycleProduct->status === ProductAdminStatus::Archived->value;
    }

    protected function createComponentProductArchiveForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->lifecycleProduct === null
            || $this->lifecycleProduct->status === ProductAdminStatus::Archived->value) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);
        $form->addProtection('products.lifecycle.error.invalid');
        $form->addHidden('productId')->setDefaultValue((string) $this->lifecycleProduct->id);
        $form->addSubmit('confirm', 'products.lifecycle.archive.confirm')
            ->setHtmlAttribute('class', 'btn btn-danger');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->lifecycleProduct === null
                || !is_string($values['productId'] ?? null)
                || (int) $values['productId'] !== $this->lifecycleProduct->id) {
                throw new ForbiddenRequestException();
            }

            try {
                $this->productLifecycleWriter->archive($this->lifecycleProduct->id);
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('products.lifecycle.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.lifecycle.archive.success'), 'success');
            $this->redirect('default');
        };

        return $form;
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
            if ($status !== ProductAdminStatus::Archived) {
                $statusOptions[$status->value] = 'products.admin.status.' . $status->value;
            }
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
                ->setHtmlAttribute('data-hugerte', 'product-description')
                ->setHtmlAttribute('data-media-picker-url', $this->link(':Admin:Media:published'))
                ->setHtmlAttribute('data-media-picker-title', $translator->translate('products.editor.media_picker.title'))
                ->setHtmlAttribute('data-media-picker-label', $translator->translate('products.editor.media_picker.label'))
                ->setHtmlAttribute('data-media-picker-cancel', $translator->translate('products.editor.media_picker.cancel'))
                ->setHtmlAttribute('data-media-picker-insert', $translator->translate('products.editor.media_picker.insert'))
                ->setHtmlAttribute('data-media-picker-unavailable', $translator->translate('products.editor.media_picker.unavailable'))
                ->setHtmlAttribute('data-media-picker-empty', $translator->translate('products.editor.media_picker.empty'))
                ->setHtmlAttribute('data-media-picker-failed', $translator->translate('products.editor.media_picker.failed'));
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
            } catch (ProductActivationRequirementsNotMet) {
                $form->addError($translator->translate('products.editor.error.activation_requirements'));
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
            $this->redirect('edit', $this->editData->id);
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
                    if (!is_string($rawValue) && !is_int($rawValue)) {
                        $form->addError($translator->translate('products.editor.error.invalid'));
                        return;
                    }
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
            usort($orderedAxes, static function (array $left, array $right): int {
                $orderComparison = $left['order'] <=> $right['order'];
                if ($orderComparison !== 0) {
                    return $orderComparison;
                }

                return $left['id'] <=> $right['id'];
            });
            $orders = array_column($orderedAxes, 'order');
            if (count($orders) !== count(array_unique($orders))) {
                $form->addError($translator->translate('products.editor.error.invalid'));
                return;
            }
            $axisIds = array_map(static fn(array $axis): int => $axis['id'], $orderedAxes);

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

    protected function createComponentProductImageManagementForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->editData === null || $this->productImages === []) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $imageOptions = [];
        foreach ($this->productImages as $image) {
            $imageOptions[$image->id] = $image->altText ?? sprintf('Media #%d', $image->mediaAssetId);
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('imageId', 'products.images.form.image', $imageOptions)
            ->setRequired()
            ->setHtmlAttribute('class', 'form-select');
        $form->addText('altText', 'products.images.form.alt_text')
            ->setMaxLength(255)
            ->setHtmlAttribute('class', 'form-control');
        $form->addInteger('sortOrder', 'products.images.form.sort_order')
            ->setHtmlAttribute('class', 'form-control');
        $form->addSelect('action', 'products.images.form.action', [
            'update' => 'products.images.form.action.update',
            'primary' => 'products.images.form.action.primary',
            'remove' => 'products.images.form.action.remove',
        ])
            ->setRequired()
            ->setHtmlAttribute('class', 'form-select');
        $form->addSubmit('submit', 'products.images.form.submit')
            ->setHtmlAttribute('class', 'btn btn-outline-primary');

        $firstImage = $this->productImages[0];
        $form->setDefaults([
            'imageId' => (string) $firstImage->id,
            'altText' => $firstImage->altText ?? '',
            'sortOrder' => $firstImage->sortOrder,
            'action' => 'update',
        ]);

        $imageConfiguration = [];
        foreach ($this->productImages as $image) {
            $imageConfiguration[(string) $image->id] = [
                'altText' => $image->altText ?? '',
                'sortOrder' => $image->sortOrder,
            ];
        }
        $form->getElementPrototype()
            ->setAttribute('data-product-image-management', '')
            ->setAttribute(
                'data-image-configuration',
                json_encode($imageConfiguration, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            );

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null) {
                throw new ForbiddenRequestException();
            }

            $imageIdRaw = $values['imageId'] ?? null;
            $action = $values['action'] ?? null;
            if ((!is_string($imageIdRaw) && !is_int($imageIdRaw)) || !is_string($action)) {
                $form->addError($translator->translate('products.images.form.error.invalid'));
                return;
            }

            $imageId = filter_var((string) $imageIdRaw, FILTER_VALIDATE_INT);
            if (!is_int($imageId) || $imageId < 1) {
                $form->addError($translator->translate('products.images.form.error.invalid'));
                return;
            }

            if ($action === 'remove') {
                $this->productImageWriter->remove($this->editData->id, $imageId);
                $message = 'products.images.form.removed';
            } elseif ($action === 'primary') {
                $this->productImageWriter->setPrimary($this->editData->id, $imageId);
                $message = 'products.images.form.primary_set';
            } elseif ($action === 'update') {
                $altText = $values['altText'] ?? null;
                $sortOrderRaw = $values['sortOrder'] ?? null;
                if (
                    !is_string($altText)
                    || (!is_string($sortOrderRaw) && !is_int($sortOrderRaw))
                ) {
                    $form->addError($translator->translate('products.images.form.error.invalid'));
                    return;
                }
                $sortOrder = filter_var((string) $sortOrderRaw, FILTER_VALIDATE_INT);
                if (!is_int($sortOrder) || $sortOrder < 0) {
                    $form->addError($translator->translate('products.images.form.error.invalid'));
                    return;
                }

                $this->productImageWriter->update(
                    $this->editData->id,
                    $imageId,
                    trim($altText) === '' ? null : trim($altText),
                    $sortOrder,
                );
                $message = 'products.images.form.updated';
            } else {
                $form->addError($translator->translate('products.images.form.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate($message), 'success');
            $this->redirect('edit', $this->editData->id);
        };

        return $form;
    }

    protected function createComponentProductRelationAddForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->editData === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('relatedProductId', 'products.relations.add.product', $this->productRelationOptions)
            ->setPrompt('products.relations.add.product_prompt')
            ->setRequired('products.relations.error.required')
            ->setHtmlAttribute('class', 'form-select');
        $form->addSelect('type', 'products.relations.add.type', [
            ProductRelationKind::Accessory->value => 'products.relations.type.accessory',
            ProductRelationKind::Alternative->value => 'products.relations.type.alternative',
            ProductRelationKind::Compatible->value => 'products.relations.type.compatible',
        ])
            ->setRequired()
            ->setHtmlAttribute('class', 'form-select');
        $form->addSubmit('submit', 'products.relations.add.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null) {
                throw new ForbiddenRequestException();
            }

            $relatedIdRaw = $values['relatedProductId'] ?? null;
            $typeRaw = $values['type'] ?? null;
            if ((!is_string($relatedIdRaw) && !is_int($relatedIdRaw)) || !is_string($typeRaw)) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }

            $relatedId = filter_var((string) $relatedIdRaw, FILTER_VALIDATE_INT);
            $type = ProductRelationKind::tryFrom($typeRaw);
            if (!is_int($relatedId) || $relatedId < 1 || $type === null) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }

            try {
                $nextSortOrder = 0;
                foreach ($this->productRelations as $relation) {
                    $nextSortOrder = max($nextSortOrder, $relation->sortOrder + 1);
                }
                $this->productRelationWriter->add($this->editData->id, $relatedId, $type, $nextSortOrder);
            } catch (InvalidArgumentException|\DomainException|UniqueConstraintViolationException) {
                $form->addError($translator->translate('products.relations.error.duplicate_or_invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.relations.added'), 'success');
            $this->redirect('edit', $this->editData->id);
        };

        return $form;
    }

    protected function createComponentProductRelationRemoveForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->editData === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $relationOptions = [];
        foreach ($this->productRelations as $relation) {
            $relationKey = $relation->productId . ':' . $relation->type->value;
            $typeLabel = match ($relation->type) {
                ProductRelationKind::Accessory => $translator->translate('products.relations.type.accessory'),
                ProductRelationKind::Alternative => $translator->translate('products.relations.type.alternative'),
                ProductRelationKind::Compatible => $translator->translate('products.relations.type.compatible'),
            };
            $relationOptions[$relationKey] = ($relation->name ?? $relation->productCode)
                . ' (' . $relation->productCode . ') · ' . $typeLabel;
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('relationKey', 'products.relations.remove.relation', $relationOptions)
            ->setPrompt('products.relations.remove.prompt')
            ->setRequired('products.relations.error.required')
            ->setHtmlAttribute('class', 'form-select');
        $form->addSubmit('submit', 'products.relations.remove.submit')
            ->setHtmlAttribute('class', 'btn btn-outline-danger');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null) {
                throw new ForbiddenRequestException();
            }

            $key = $values['relationKey'] ?? null;
            if (!is_string($key) || preg_match('/\A([1-9][0-9]*):(accessory|alternative|compatible)\z/D', $key, $matches) !== 1) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }
            $relatedId = filter_var($matches[1], FILTER_VALIDATE_INT);
            $type = ProductRelationKind::from($matches[2]);
            if (!is_int($relatedId)) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }

            $this->productRelationWriter->remove($this->editData->id, $relatedId, $type);
            $this->flashMessage($translator->translate('products.relations.removed'), 'success');
            $this->redirect('edit', $this->editData->id);
        };

        return $form;
    }

    protected function createComponentProductRelationOrderForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->editData === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);
        $positions = $form->addContainer('positions');
        $defaults = [];
        foreach ($this->productRelations as $index => $relation) {
            $key = 'r' . $relation->productId . '_' . $relation->type->value;
            $positions->addInteger($key, 'products.relations.order.position')
                ->setRequired()
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('min', '0');
            $defaults['positions'][$key] = $index;
        }
        $form->setDefaults($defaults);
        $form->addSubmit('submit', 'products.relations.order.submit')
            ->setHtmlAttribute('class', 'btn btn-outline-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null) {
                throw new ForbiddenRequestException();
            }

            $submitted = $values['positions'] ?? null;
            if (!is_array($submitted) || count($submitted) !== count($this->productRelations)) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }

            $ordered = [];
            $seenPositions = [];
            foreach ($this->productRelations as $relation) {
                $key = 'r' . $relation->productId . '_' . $relation->type->value;
                $position = $submitted[$key] ?? null;
                if ((!is_int($position) && !is_string($position)) || filter_var((string) $position, FILTER_VALIDATE_INT) === false) {
                    $form->addError($translator->translate('products.relations.error.invalid'));
                    return;
                }
                $position = (int) $position;
                if ($position < 0 || isset($seenPositions[$position])) {
                    $form->addError($translator->translate('products.relations.error.position'));
                    return;
                }
                $seenPositions[$position] = true;
                $ordered[] = ['position' => $position, 'relation' => new ProductRelationOrder($relation->productId, $relation->type)];
            }

            usort($ordered, static fn(array $a, array $b): int => $a['position'] <=> $b['position']);
            $relations = array_map(static fn(array $entry): ProductRelationOrder => $entry['relation'], $ordered);

            try {
                $this->productRelationWriter->reorder($this->editData->id, $relations);
            } catch (InvalidArgumentException|\DomainException) {
                $form->addError($translator->translate('products.relations.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.relations.order.saved'), 'success');
            $this->redirect('edit', $this->editData->id);
        };

        return $form;
    }

    protected function createComponentProductCustomerOptionsForm(): Form
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        if ($this->editData === null || $this->customerOptionData === null) {
            throw new BadRequestException('Konfigurace zákaznických voleb není inicializována.');
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
        $form = new Form();
        $form->setTranslator($translator);

        $defaults = [];
        $optionsContainer = $form->addContainer('options');
        foreach ($this->customerOptionData->options as $option) {
            $optionKey = 'option_' . $option->id;
            $container = $optionsContainer->addContainer($optionKey);
            $container->addText('code', 'products.customer_options.option.code')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired();
            $container->addSelect('type', 'products.customer_options.option.type', [
                'choice' => 'products.customer_options.type.choice',
                'text' => 'products.customer_options.type.text',
            ])->setHtmlAttribute('class', 'form-select')->setRequired();
            $container->addCheckbox('isRequired', 'products.customer_options.option.required')
                ->setHtmlAttribute('class', 'form-check-input');
            $container->addInteger('sortOrder', 'products.customer_options.option.sort_order')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired();

            $translations = $container->addContainer('translations');
            foreach ($this->localeProvider->getActiveLocales() as $locale) {
                $translations->addText($locale->getCode(), 'products.customer_options.option.name')
                    ->setHtmlAttribute('class', 'form-control')
                    ->setRequired();
            }

            $valuesContainer = $container->addContainer('values');
            foreach ($option->values as $value) {
                $valueKey = 'value_' . $value->id;
                $valueContainer = $valuesContainer->addContainer($valueKey);
                $valueContainer->addText('code', 'products.customer_options.value.code')
                    ->setHtmlAttribute('class', 'form-control')
                    ->setRequired();
                $valueContainer->addInteger('sortOrder', 'products.customer_options.value.sort_order')
                    ->setHtmlAttribute('class', 'form-control')
                    ->setRequired();
                $valueContainer->addCheckbox('delete', 'products.customer_options.value.delete')
                    ->setHtmlAttribute('class', 'form-check-input');
                $valueTranslations = $valueContainer->addContainer('translations');
                foreach ($this->localeProvider->getActiveLocales() as $locale) {
                    $valueTranslations->addText($locale->getCode(), 'products.customer_options.value.label')
                        ->setHtmlAttribute('class', 'form-control')
                        ->setRequired();
                }
                $defaults['options'][$optionKey]['values'][$valueKey] = [
                    'code' => $value->code,
                    'sortOrder' => $value->sortOrder,
                    'translations' => $value->translations,
                ];
            }

            $newValue = $valuesContainer->addContainer('newValue');
            $newValue->addText('code', 'products.customer_options.value.code')
                ->setHtmlAttribute('class', 'form-control');
            $newValue->addInteger('sortOrder', 'products.customer_options.value.sort_order')
                ->setHtmlAttribute('class', 'form-control');
            $newValueTranslations = $newValue->addContainer('translations');
            foreach ($this->localeProvider->getActiveLocales() as $locale) {
                $newValueTranslations->addText($locale->getCode(), 'products.customer_options.value.label')
                    ->setHtmlAttribute('class', 'form-control');
            }

            $defaults['options'][$optionKey] = [
                'code' => $option->code,
                'type' => $option->type,
                'isRequired' => $option->isRequired,
                'sortOrder' => $option->sortOrder,
                'translations' => $option->translations,
                'values' => $defaults['options'][$optionKey]['values'] ?? [],
            ];
            $container->addCheckbox('delete', 'products.customer_options.option.delete')
                ->setHtmlAttribute('class', 'form-check-input');
            $defaults['options'][$optionKey]['values']['newValue']['sortOrder'] = count($option->values);
        }

        $newOption = $form->addContainer('newOption');
        $newOption->addText('code', 'products.customer_options.option.code')
            ->setHtmlAttribute('class', 'form-control');
        $newOption->addSelect('type', 'products.customer_options.option.type', [
            'choice' => 'products.customer_options.type.choice',
            'text' => 'products.customer_options.type.text',
        ])->setHtmlAttribute('class', 'form-select');
        $newOption->addCheckbox('isRequired', 'products.customer_options.option.required')
            ->setHtmlAttribute('class', 'form-check-input');
        $newOption->addInteger('sortOrder', 'products.customer_options.option.sort_order')
            ->setHtmlAttribute('class', 'form-control');
        $newTranslations = $newOption->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $newTranslations->addText($locale->getCode(), 'products.customer_options.option.name')
                ->setHtmlAttribute('class', 'form-control');
        }
        $newValues = $newOption->addContainer('values');
        $newValue = $newValues->addContainer('newValue');
        $newValue->addText('code', 'products.customer_options.value.code')
            ->setHtmlAttribute('class', 'form-control');
        $newValue->addInteger('sortOrder', 'products.customer_options.value.sort_order')
            ->setHtmlAttribute('class', 'form-control');
        $newValueTranslations = $newValue->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $newValueTranslations->addText($locale->getCode(), 'products.customer_options.value.label')
                ->setHtmlAttribute('class', 'form-control');
        }

        $defaults['newOption'] = [
            'type' => 'choice',
            'sortOrder' => count($this->customerOptionData->options),
            'values' => ['newValue' => ['sortOrder' => 0]],
        ];
        $form->setDefaults($defaults);

        $form->addSubmit('submit', 'products.customer_options.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage() || $this->editData === null || $this->customerOptionData === null) {
                throw new ForbiddenRequestException();
            }

            $options = [];
            $submittedOptions = $values['options'] ?? [];
            if (!is_array($submittedOptions)) {
                $form->addError($translator->translate('products.customer_options.error.invalid'));
                return;
            }

            foreach ($this->customerOptionData->options as $option) {
                $optionKey = 'option_' . $option->id;
                $entry = $submittedOptions[$optionKey] ?? null;
                if (!is_array($entry)) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }
                if (($entry['delete'] ?? false) === true) {
                    continue;
                }

                if (!is_string($entry['code'] ?? null)
                    || !is_string($entry['type'] ?? null)
                    || !is_bool($entry['isRequired'] ?? null)
                    || (!is_int($entry['sortOrder'] ?? null) && !is_string($entry['sortOrder'] ?? null))) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }
                $translations = $this->readCustomerOptionTranslations($entry['translations'] ?? null);
                $sortOrder = $this->readCustomerOptionSortOrder($entry['sortOrder']);
                if ($translations === null || $sortOrder === null) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }

                $type = $entry['type'];
                if (!in_array($type, ['choice', 'text'], true)) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }

                $optionValues = [];
                if ($type === 'choice') {
                    $rawValues = $entry['values'] ?? null;
                    if (!is_array($rawValues)) {
                        $form->addError($translator->translate('products.customer_options.error.invalid'));
                        return;
                    }
                    foreach ($option->values as $value) {
                        $valueKey = 'value_' . $value->id;
                        $valueEntry = $rawValues[$valueKey] ?? null;
                        if (!is_array($valueEntry)) {
                            $form->addError($translator->translate('products.customer_options.error.invalid'));
                            return;
                        }
                        if (($valueEntry['delete'] ?? false) === true) {
                            continue;
                        }
                        if (!is_string($valueEntry['code'] ?? null)
                            || (!is_int($valueEntry['sortOrder'] ?? null) && !is_string($valueEntry['sortOrder'] ?? null))) {
                            $form->addError($translator->translate('products.customer_options.error.invalid'));
                            return;
                        }
                        $valueTranslations = $this->readCustomerOptionTranslations($valueEntry['translations'] ?? null);
                        $valueSortOrder = $this->readCustomerOptionSortOrder($valueEntry['sortOrder']);
                        if ($valueTranslations === null || $valueSortOrder === null) {
                            $form->addError($translator->translate('products.customer_options.error.invalid'));
                            return;
                        }
                        $optionValues[] = [
                            'id' => $value->id,
                            'code' => $valueEntry['code'],
                            'sortOrder' => $valueSortOrder,
                            'translations' => $valueTranslations,
                        ];
                    }

                    $newValueEntry = $rawValues['newValue'] ?? null;
                    if (is_array($newValueEntry) && $this->hasCustomerOptionInput($newValueEntry['code'] ?? null, $newValueEntry['translations'] ?? null)) {
                        if (!is_string($newValueEntry['code'] ?? null)
                            || (!is_int($newValueEntry['sortOrder'] ?? null) && !is_string($newValueEntry['sortOrder'] ?? null))) {
                            $form->addError($translator->translate('products.customer_options.error.invalid'));
                            return;
                        }
                        $valueTranslations = $this->readCustomerOptionTranslations($newValueEntry['translations'] ?? null);
                        $valueSortOrder = $this->readCustomerOptionSortOrder($newValueEntry['sortOrder']);
                        if ($valueTranslations === null || $valueSortOrder === null) {
                            $form->addError($translator->translate('products.customer_options.error.invalid'));
                            return;
                        }
                        $optionValues[] = [
                            'code' => $newValueEntry['code'],
                            'sortOrder' => $valueSortOrder,
                            'translations' => $valueTranslations,
                        ];
                    }
                }

                $options[] = [
                    'id' => $option->id,
                    'code' => $entry['code'],
                    'type' => $type,
                    'isRequired' => $entry['isRequired'],
                    'sortOrder' => $sortOrder,
                    'translations' => $translations,
                    'values' => $optionValues,
                ];
            }

            $newEntry = $values['newOption'] ?? null;
            if (is_array($newEntry) && $this->hasCustomerOptionInput($newEntry['code'] ?? null, $newEntry['translations'] ?? null)) {
                if (!is_string($newEntry['code'] ?? null)
                    || !is_string($newEntry['type'] ?? null)
                    || !is_bool($newEntry['isRequired'] ?? null)
                    || (!is_int($newEntry['sortOrder'] ?? null) && !is_string($newEntry['sortOrder'] ?? null))) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }
                $translations = $this->readCustomerOptionTranslations($newEntry['translations'] ?? null);
                $sortOrder = $this->readCustomerOptionSortOrder($newEntry['sortOrder']);
                if ($translations === null || $sortOrder === null || !in_array($newEntry['type'], ['choice', 'text'], true)) {
                    $form->addError($translator->translate('products.customer_options.error.invalid'));
                    return;
                }

                $newOptionValues = [];
                $newValueEntry = is_array($newEntry['values'] ?? null) ? $newEntry['values']['newValue'] ?? null : null;
                if ($newEntry['type'] === 'choice' && is_array($newValueEntry)
                    && $this->hasCustomerOptionInput($newValueEntry['code'] ?? null, $newValueEntry['translations'] ?? null)) {
                    if (!is_string($newValueEntry['code'] ?? null)
                        || (!is_int($newValueEntry['sortOrder'] ?? null) && !is_string($newValueEntry['sortOrder'] ?? null))) {
                        $form->addError($translator->translate('products.customer_options.error.invalid'));
                        return;
                    }
                    $valueTranslations = $this->readCustomerOptionTranslations($newValueEntry['translations'] ?? null);
                    $valueSortOrder = $this->readCustomerOptionSortOrder($newValueEntry['sortOrder']);
                    if ($valueTranslations === null || $valueSortOrder === null) {
                        $form->addError($translator->translate('products.customer_options.error.invalid'));
                        return;
                    }
                    $newOptionValues[] = [
                        'code' => $newValueEntry['code'],
                        'sortOrder' => $valueSortOrder,
                        'translations' => $valueTranslations,
                    ];
                }

                $options[] = [
                    'code' => $newEntry['code'],
                    'type' => $newEntry['type'],
                    'isRequired' => $newEntry['isRequired'],
                    'sortOrder' => $sortOrder,
                    'translations' => $translations,
                    'values' => $newOptionValues,
                ];
            }

            try {
                $this->productCustomerOptionWriter->save($this->editData->id, $options);
            } catch (UniqueConstraintViolationException|InvalidArgumentException) {
                $form->addError($translator->translate('products.customer_options.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.customer_options.saved'), 'success');
            $this->redirect('edit', $this->editData->id);
        };

        return $form;
    }

    /** @return array<string,string>|null */
    private function readCustomerOptionTranslations(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }
        $translations = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $value = $raw[$code] ?? null;
            if (!is_string($value)) {
                return null;
            }
            $translations[$code] = $value;
        }
        return $translations;
    }

    private function readCustomerOptionSortOrder(mixed $raw): ?int
    {
        if (is_int($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $value = filter_var($raw, FILTER_VALIDATE_INT);
            return is_int($value) ? $value : null;
        }
        return null;
    }

    private function hasCustomerOptionInput(mixed $code, mixed $translations): bool
    {
        if (is_string($code) && trim($code) !== '') {
            return true;
        }
        if (!is_array($translations)) {
            return false;
        }
        foreach ($translations as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }
        return false;
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
