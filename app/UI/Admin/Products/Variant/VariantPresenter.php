<?php

declare(strict_types=1);

namespace App\UI\Admin\Products\Variant;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductVariantEditorReader;
use App\Modules\Catalog\Api\ProductVariantWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\AdminAccessTrait;
use InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;

final class VariantPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?\App\Modules\Catalog\Api\Dto\ProductVariantEditorData $variant = null;

    public function __construct(
        private readonly ProductVariantEditorReader $variantReader,
        private readonly ProductVariantWriter $variantWriter,
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

    public function actionEdit(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $this->variant = $this->variantReader->findForEdit($id, $this->localeProvider->getDefault());
        if ($this->variant === null) {
            $translator = $this->translatorFactory->createTranslator(
                TranslationDomain::Catalog,
                $this->localeProvider->getDefault(),
            );
            $this->flashMessage($translator->translate('products.variant_editor.not_found'), 'warning');
            $this->redirect(':Admin:Products:default');
        }

        $this->template->variant = $this->variant;
    }

    protected function createComponentVariantForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->variant === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addText('sku', 'products.variant_editor.sku')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired();
        $form->addText('ean', 'products.variant_editor.ean')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('inputmode', 'numeric')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addText('weightG', 'products.variant_editor.weight')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('inputmode', 'numeric')
            ->setHtmlAttribute('min', '0');
        $form->addCheckbox('isDefault', 'products.variant_editor.default');
        $form->addSubmit('submit', 'products.variant_editor.save')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->setDefaults([
            'sku' => $this->variant->sku,
            'ean' => $this->variant->ean ?? '',
            'weightG' => $this->variant->weightG === null ? '' : (string) $this->variant->weightG,
            'isDefault' => $this->variant->isDefault,
        ]);

        $variantId = $this->variant->id;
        $productId = $this->variant->productId;
        $form->onSuccess[] = function (Form $form, array $values) use ($translator, $variantId, $productId): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            if (
                !is_string($values['sku'] ?? null)
                || !is_string($values['ean'] ?? null)
                || (!is_string($values['weightG'] ?? null) && !is_int($values['weightG'] ?? null))
                || !is_bool($values['isDefault'] ?? null)
            ) {
                $form->addError($translator->translate('products.variant_editor.error.invalid'));
                return;
            }

            $weightInput = trim((string) $values['weightG']);
            $weightG = null;
            if ($weightInput !== '') {
                $parsedWeight = filter_var($weightInput, FILTER_VALIDATE_INT);
                if (!is_int($parsedWeight) || $parsedWeight < 0 || $parsedWeight > 4294967295) {
                    $form->addError($translator->translate('products.variant_editor.error.invalid'));
                    return;
                }
                $weightG = $parsedWeight;
            }

            try {
                $this->variantWriter->update(
                    $variantId,
                    $values['sku'],
                    $values['ean'],
                    $weightG,
                    $values['isDefault'],
                );
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('products.variant_editor.error.duplicate'));
                return;
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('products.variant_editor.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('products.variant_editor.updated'), 'success');
            $this->redirect(':Admin:Products:Variants:default', ['id' => $productId]);
        };

        return $form;
    }
}
