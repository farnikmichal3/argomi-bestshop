<?php

declare(strict_types=1);

namespace App\UI\Admin\Catalog\Attributes;

use App\Modules\Catalog\Api\AttributeAdminReader;
use App\Modules\Catalog\Api\AttributeInUseException;
use App\Modules\Catalog\Api\AttributeDataType;
use App\Modules\Catalog\Api\AttributeWriter;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\Dto\AttributeEditorData;
use App\Modules\Catalog\Api\Dto\AttributeValueEditorData;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\AdminAccessTrait;
use InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use stdClass;

final class AttributesPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?AttributeEditorData $attribute = null;
    private ?AttributeValueEditorData $attributeValue = null;

    public function __construct(
        private readonly AttributeAdminReader $attributeReader,
        private readonly AttributeWriter $attributeWriter,
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
        $locale = $this->localeProvider->getDefault();
        $this->template->attributes = $this->attributeReader->findAll($locale);
        $this->template->canManage = $this->catalogPermission->canManage();
    }

    public function actionCreate(): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        $this->template->locales = $this->localeProvider->getActiveLocales();
        $this->template->unitOptions = $this->attributeReader->findUnitOptions($this->localeProvider->getDefault());
    }

    public function actionEdit(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        $this->attribute = $this->attributeReader->findForEdit($id);
        if ($this->attribute === null) {
            $this->redirect('default');
        }
        $this->template->attribute = $this->attribute;
        $this->template->locales = $this->localeProvider->getActiveLocales();
        $this->template->unitOptions = $this->attributeReader->findUnitOptions($this->localeProvider->getDefault());
    }

    public function actionEditValue(int $id, int $valueId): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }
        $this->attribute = $this->attributeReader->findForEdit($id);
        if ($this->attribute === null || $this->attribute->type !== AttributeDataType::Enum) {
            $this->redirect('default');
        }
        foreach ($this->attribute->values as $value) {
            if ($value->id === $valueId) {
                $this->attributeValue = $value;
                break;
            }
        }
        if ($this->attributeValue === null) {
            $this->redirect('edit', ['id' => $id]);
        }
        $this->template->attribute = $this->attribute;
        $this->template->attributeValue = $this->attributeValue;
        $this->template->locales = $this->localeProvider->getActiveLocales();
    }

    protected function createComponentCreateForm(): Form
    {
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $this->addAttributeFields($form);
        $form->addSubmit('submit', 'attributes.form.create.submit')->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            $parsed = $this->parseAttributeValues($values);
            if ($parsed === null) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->create(...$parsed);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('attributes.form.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.form.created'), 'success');
            $this->redirect('default');
        };
        return $form;
    }

    protected function createComponentEditForm(): Form
    {
        $attribute = $this->attribute;
        if ($attribute === null) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $this->addAttributeFields($form);
        $form->setDefaults([
            'code' => $attribute->code,
            'type' => $attribute->type->value,
            'unitId' => $attribute->unitId === null ? '' : (string) $attribute->unitId,
            'isFilterable' => $attribute->isFilterable,
            'sortOrder' => $attribute->sortOrder,
            'translations' => array_map(static fn(string $name): array => ['name' => $name], $attribute->translations),
        ]);
        $form->addSubmit('submit', 'attributes.form.edit.submit')->setHtmlAttribute('class', 'btn btn-primary');
        $attributeId = $attribute->id;
        $form->onSuccess[] = function (Form $form, array $values) use ($translator, $attributeId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            $parsed = $this->parseAttributeValues($values);
            if ($parsed === null) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->update($attributeId, ...$parsed);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('attributes.form.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.form.updated'), 'success');
            $this->redirect('edit', ['id' => $attributeId]);
        };
        return $form;
    }

    protected function createComponentDeleteForm(): Form
    {
        if ($this->attribute === null) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $form->addHidden('attributeId')->setDefaultValue((string) $this->attribute->id);
        $form->addSubmit('delete', 'attributes.delete.submit')->setHtmlAttribute('class', 'btn btn-outline-danger');
        $attributeId = $this->attribute->id;
        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator, $attributeId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            if (!is_string($values->attributeId) || $values->attributeId !== (string) $attributeId) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->delete($attributeId);
            } catch (AttributeInUseException) {
                $form->addError($translator->translate('attributes.delete.in_use'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.delete.deleted'), 'success');
            $this->redirect('default');
        };
        return $form;
    }

    protected function createComponentCreateValueForm(): Form
    {
        $attribute = $this->attribute;
        if ($attribute === null || $attribute->type !== AttributeDataType::Enum) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $form->addText('code', 'attributes.values.code')->setHtmlAttribute('class', 'form-control')->setRequired('attributes.form.error.required');
        $form->addInteger('sortOrder', 'attributes.values.sort_order')->setHtmlAttribute('class', 'form-control')->setDefaultValue(0)->addRule(Form::Min, 'attributes.form.error.invalid', 0);
        $this->addValueTranslationFields($form);
        $form->addSubmit('submit', 'attributes.values.create.submit')->setHtmlAttribute('class', 'btn btn-primary');
        $attributeId = $attribute->id;
        $form->onSuccess[] = function (Form $form, array $values) use ($translator, $attributeId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            if (!is_string($values['code'] ?? null) || !is_int($values['sortOrder'] ?? null) || ($translations = $this->normalizeTranslations($values['translations'] ?? null)) === null) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->createValue($attributeId, $values['code'], $values['sortOrder'], $translations);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('attributes.form.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.values.created'), 'success');
            $this->redirect('edit', ['id' => $attributeId]);
        };
        return $form;
    }

    protected function createComponentEditValueForm(): Form
    {
        $attribute = $this->attribute;
        $attributeValue = $this->attributeValue;
        if ($attribute === null || $attributeValue === null) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $form->addText('code', 'attributes.values.code')->setHtmlAttribute('class', 'form-control')->setRequired('attributes.form.error.required');
        $form->addInteger('sortOrder', 'attributes.values.sort_order')->setHtmlAttribute('class', 'form-control')->addRule(Form::Min, 'attributes.form.error.invalid', 0);
        $this->addValueTranslationFields($form);
        $form->setDefaults([
            'code' => $attributeValue->code,
            'sortOrder' => $attributeValue->sortOrder,
            'translations' => array_map(static fn(string $label): array => ['label' => $label], $attributeValue->translations),
        ]);
        $form->addSubmit('submit', 'attributes.values.edit.submit')->setHtmlAttribute('class', 'btn btn-primary');
        $attributeId = $attribute->id;
        $valueId = $attributeValue->id;
        $form->onSuccess[] = function (Form $form, array $values) use ($translator, $attributeId, $valueId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            if (!is_string($values['code'] ?? null) || !is_int($values['sortOrder'] ?? null) || ($translations = $this->normalizeTranslations($values['translations'] ?? null)) === null) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->updateValue($attributeId, $valueId, $values['code'], $values['sortOrder'], $translations);
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('attributes.form.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.values.updated'), 'success');
            $this->redirect('edit', ['id' => $attributeId]);
        };
        return $form;
    }

    protected function createComponentDeleteValueForm(): Form
    {
        if ($this->attribute === null || $this->attributeValue === null) {
            throw new ForbiddenRequestException();
        }
        $translator = $this->translator();
        $form = new Form();
        $form->setTranslator($translator);
        $form->addHidden('valueId')->setDefaultValue((string) $this->attributeValue->id);
        $form->addSubmit('delete', 'attributes.values.delete.submit')->setHtmlAttribute('class', 'btn btn-outline-danger');
        $attributeId = $this->attribute->id;
        $valueId = $this->attributeValue->id;
        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator, $attributeId, $valueId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }
            if (!is_string($values->valueId) || $values->valueId !== (string) $valueId) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            try {
                $this->attributeWriter->deleteValue($attributeId, $valueId);
            } catch (AttributeInUseException) {
                $form->addError($translator->translate('attributes.values.in_use'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('attributes.form.error.invalid'));
                return;
            }
            $this->flashMessage($translator->translate('attributes.values.deleted'), 'success');
            $this->redirect('edit', ['id' => $attributeId]);
        };
        return $form;
    }

    private function addAttributeFields(Form $form): void
    {
        $form->addText('code', 'attributes.form.code')->setHtmlAttribute('class', 'form-control')->setRequired('attributes.form.error.required');
        $form->addSelect('type', 'attributes.form.type', [
            'text' => 'attributes.type.text',
            'number' => 'attributes.type.number',
            'enum' => 'attributes.type.enum',
            'bool' => 'attributes.type.bool',
        ])->setHtmlAttribute('class', 'form-select')->setRequired('attributes.form.error.required');
        $form->addSelect('unitId', 'attributes.form.unit', $this->attributeReader->findUnitOptions($this->localeProvider->getDefault()))
            ->setHtmlAttribute('class', 'form-select')->setPrompt('attributes.form.unit.none');
        $form->addCheckbox('isFilterable', 'attributes.form.filterable');
        $form->addInteger('sortOrder', 'attributes.form.sort_order')->setHtmlAttribute('class', 'form-control')->setDefaultValue(0)->addRule(Form::Min, 'attributes.form.error.invalid', 0);
        $translations = $form->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $translations->addContainer($locale->getCode())
                ->addText('name', 'attributes.form.name')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('attributes.form.error.required');
        }
    }

    private function addValueTranslationFields(Form $form): void
    {
        $translations = $form->addContainer('translations');
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $translations->addContainer($locale->getCode())
                ->addText('label', 'attributes.values.label')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('attributes.form.error.required');
        }
    }

    /**
     * @param array<mixed, mixed> $values
     * @return array{string, AttributeDataType, ?int, bool, int, array<string, mixed>}|null
     */
    private function parseAttributeValues(array $values): ?array
    {
        if (
            !is_string($values['code'] ?? null)
            || !is_string($values['type'] ?? null)
            || !is_string($values['unitId'] ?? null) && !is_int($values['unitId'] ?? null)
            || !is_bool($values['isFilterable'] ?? null)
            || !is_int($values['sortOrder'] ?? null)
            || !is_array($values['translations'] ?? null)
        ) {
            return null;
        }
        $type = AttributeDataType::tryFrom($values['type']);
        if ($type === null) {
            return null;
        }
        $unitInput = (string) $values['unitId'];
        $unitId = null;
        if ($unitInput !== '') {
            $parsed = filter_var($unitInput, FILTER_VALIDATE_INT);
            if (!is_int($parsed) || $parsed < 1) {
                return null;
            }
            $unitId = $parsed;
        }
        $translations = $this->normalizeTranslations($values['translations']);
        if ($translations === null) {
            return null;
        }
        return [$values['code'], $type, $unitId, $values['isFilterable'], $values['sortOrder'], $translations];
    }

    /** @return array<string, mixed>|null */
    private function normalizeTranslations(mixed $translations): ?array
    {
        if (!is_array($translations)) {
            return null;
        }
        $normalized = [];
        foreach ($translations as $locale => $translation) {
            if (!is_string($locale) || !is_array($translation)) {
                return null;
            }
            $normalized[$locale] = $translation;
        }
        return $normalized;
    }

    private function translator(): \Nette\Localization\Translator
    {
        return $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );
    }
}
