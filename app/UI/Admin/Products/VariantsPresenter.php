<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\Dto\ProductVariantMatrix;
use App\Modules\Catalog\Api\Dto\VariantMatrixCombination;
use App\Modules\Catalog\Api\ProductVariantGenerator;
use App\Modules\Catalog\Api\ProductVariantMatrixReader;
use App\Modules\Catalog\Api\VariantMatrixTooLargeException;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\AdminAccessTrait;
use InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Localization\Translator;

final class VariantsPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?ProductVariantMatrix $matrix = null;

    public function __construct(
        private readonly ProductVariantMatrixReader $matrixReader,
        private readonly ProductVariantGenerator $variantGenerator,
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

    public function actionDefault(int $id): void
    {
        if (!$this->catalogPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        try {
            $this->matrix = $this->matrixReader->findForProduct($id, $this->localeProvider->getDefault());
        } catch (VariantMatrixTooLargeException) {
            $translator = $this->translatorFactory->createTranslator(
                TranslationDomain::Catalog,
                $this->localeProvider->getDefault(),
            );
            $this->flashMessage($translator->translate('products.variants.limit'), 'warning');
            $this->redirect(':Admin:Products:edit', ['id' => $id]);
        }

        if ($this->matrix === null) {
            $translator = $this->translatorFactory->createTranslator(
                TranslationDomain::Catalog,
                $this->localeProvider->getDefault(),
            );
            $this->flashMessage($translator->translate('products.variants.product_not_found'), 'warning');
            $this->redirect(':Admin:Products:default');
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );

        $rows = [];
        $missingOptions = [];
        foreach ($this->matrix->combinations as $combination) {
            $label = $this->formatCombinationLabel($combination, $this->matrix, $translator);
            $rows[] = [
                'label' => $label,
                'sku' => $combination->sku,
                'exists' => $combination->exists(),
                'isActive' => $combination->isActive,
                'isDefault' => $combination->isDefault,
            ];
            if (!$combination->exists()) {
                $missingOptions[$combination->hash] = $label;
            }
        }

        $hasEmptyAxis = false;
        foreach ($this->matrix->axes as $axis) {
            if ($axis->values === []) {
                $hasEmptyAxis = true;
                break;
            }
        }

        $this->template->setFile(__DIR__ . '/variants.latte');
        $this->template->matrix = $this->matrix;
        $this->template->combinationRows = $rows;
        $this->template->hasAxes = $this->matrix->axes !== [];
        $this->template->hasValues = !$hasEmptyAxis && $this->matrix->combinations !== [];
        $this->template->hasMissing = $missingOptions !== [];
        $this->template->canGenerate = $missingOptions !== [];
    }

    protected function createComponentGenerateForm(): Form
    {
        if (!$this->catalogPermission->canManage() || $this->matrix === null) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Catalog,
            $this->localeProvider->getDefault(),
        );

        $options = [];
        $candidateByHash = [];
        foreach ($this->matrix->combinations as $combination) {
            if ($combination->exists()) {
                continue;
            }
            $label = $this->formatCombinationLabel($combination, $this->matrix, $translator);
            $options[$combination->hash] = $label;
            $candidateByHash[$combination->hash] = $combination;
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addMultiSelect('combinations', 'products.variants.select_combinations', $options)
            ->setHtmlAttribute('class', 'form-select')
            ->setHtmlAttribute('size', (string) min(12, max(3, count($options))));
        $form->addSubmit('submit', 'products.variants.generate')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $productId = $this->matrix->productId;
        $form->onSuccess[] = function (Form $form, array $values) use (
            $translator,
            $candidateByHash,
            $productId,
        ): void {
            if (!$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $selectedHashes = $values['combinations'] ?? null;
            if (!is_array($selectedHashes) || !array_is_list($selectedHashes) || $selectedHashes === []) {
                $form->addError($translator->translate('products.variants.generate.error'));
                return;
            }

            $combinations = [];
            $seen = [];
            foreach ($selectedHashes as $hash) {
                if (!is_string($hash) || isset($seen[$hash]) || !isset($candidateByHash[$hash])) {
                    $form->addError($translator->translate('products.variants.generate.error'));
                    return;
                }
                $seen[$hash] = true;
                $combinations[] = $candidateByHash[$hash]->values;
            }

            try {
                $created = $this->variantGenerator->generate($productId, $combinations);
            } catch (UniqueConstraintViolationException|InvalidArgumentException) {
                $form->addError($translator->translate('products.variants.generate.error'));
                return;
            }

            $message = $created > 0
                ? 'products.variants.generate.success'
                : 'products.variants.generate.unchanged';
            $this->flashMessage($translator->translate($message), 'success');
            $this->redirect('this');
        };

        return $form;
    }

    private function formatCombinationLabel(
        VariantMatrixCombination $combination,
        ProductVariantMatrix $matrix,
        Translator $translator,
    ): string {
        $parts = [];
        foreach ($matrix->axes as $axis) {
            $value = $combination->values[$axis->code]['value'];
            $label = null;
            foreach ($axis->values as $option) {
                if ($option->value === $value) {
                    $label = $option->label;
                    break;
                }
            }

            if (is_bool($value)) {
                $label = $translator->translate($value
                    ? 'products.variants.value.true'
                    : 'products.variants.value.false');
            }
            $parts[] = ($axis->name ?? $axis->code) . ': ' . ($label ?? $value);
        }

        return implode(' · ', $parts);
    }
}
