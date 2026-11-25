<?php

declare(strict_types=1);

namespace App\UI\Admin\Media;

use App\Modules\Media\Api\Dto\MediaAssetData;
use App\Modules\Media\Api\MediaAssetContentReader;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaAssetPublicationWriter;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Modules\Media\Api\MediaPermission;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductImageWriter;
use App\Modules\Catalog\Api\ProductVariantMatrixReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\AdminAccessTrait;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;

final class MediaPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    private ?int $assignProductId = null;

    private ?int $assignVariantId = null;

    /** @var array<int|string, string> */
    private array $assignVariantOptions = [];

    /** @var list<MediaAssetData> */
    private array $libraryAssets = [];

    public function __construct(
        private readonly MediaPermission $mediaPermission,
        private readonly CatalogPermission $catalogPermission,
        private readonly ProductImageWriter $productImageWriter,
        private readonly ProductVariantMatrixReader $productVariantMatrixReader,
        private readonly MediaAssetReader $mediaAssetReader,
        private readonly MediaAssetWriter $mediaAssetWriter,
        private readonly MediaAssetPublicationWriter $mediaAssetPublicationWriter,
        private readonly MediaAssetContentReader $mediaAssetContentReader,
        private readonly TranslatorFactory $translatorFactory,
        private readonly LocaleProvider $localeProvider,
    ) {
        parent::__construct();
    }

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Admin;
    }

    public function actionDefault(?int $productId = null, ?int $variantId = null): void
    {
        if (!$this->mediaPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $this->libraryAssets = $this->mediaAssetReader->findLibrary(48, 0);
        $this->assignProductId = $productId;
        $this->assignVariantId = $variantId;
        $this->assignVariantOptions = [];

        if ($productId !== null && $this->catalogPermission->canManage()) {
            $matrix = $this->productVariantMatrixReader->findForProduct($productId, $this->localeProvider->getDefault());
            if ($matrix === null) {
                throw new BadRequestException('Produkt pro přiřazení obrázků nebyl nalezen.');
            }

            $this->assignVariantOptions = [0 => 'media.assign.product_level'];
            foreach ($matrix->combinations as $combination) {
                if ($combination->variantId !== null) {
                    $this->assignVariantOptions[$combination->variantId] = $combination->label;
                }
            }
            if ($variantId !== null && !array_key_exists($variantId, $this->assignVariantOptions)) {
                throw new BadRequestException('Varianta nepatří k vybranému produktu.');
            }
        }
    }

    public function renderDefault(): void
    {
        $this->template->assets = $this->libraryAssets;
        $this->template->canManageMedia = $this->mediaPermission->canManage();
        $this->template->assignProductId = $this->assignProductId;
        $this->template->canAssignProductImages = $this->assignProductId !== null
            && $this->catalogPermission->canManage()
            && $this->libraryAssets !== [];
    }

    public function actionPublished(): void
    {
        if (!$this->mediaPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $items = [];
        foreach ($this->mediaAssetReader->findLibrary(100, 0) as $asset) {
            if ($asset->publishedAt === null || $asset->publicToken === null) {
                continue;
            }

            $items[] = [
                'title' => $asset->originalFilename,
                'value' => '/media/' . $asset->publicToken . '/detail',
                'alt' => $asset->originalFilename,
                'width' => $asset->sourceWidth,
                'height' => $asset->sourceHeight,
            ];
        }

        $this->sendJson($items);
    }

    public function actionImage(int $id, string $variant = 'thumbnail'): void
    {
        if (!$this->mediaPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        if (!in_array($variant, ['original', 'thumbnail', 'listing', 'detail'], true)) {
            throw new BadRequestException('Varianta obrázku není platná.');
        }

        $image = $this->mediaAssetContentReader->read($id, $variant);
        if ($image === null) {
            throw new BadRequestException('Požadovaný náhled média nebyl nalezen.');
        }

        $this->sendResponse(new MediaBinaryResponse($image->contents, $image->mimeType));
    }

    protected function createComponentAssignForm(): Form
    {
        if ($this->assignProductId === null || !$this->catalogPermission->canManage() || $this->libraryAssets === []) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );
        $assetOptions = [];
        foreach ($this->libraryAssets as $asset) {
            $assetOptions[$asset->id] = $asset->originalFilename;
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('mediaAssetId', 'media.assign.asset', $assetOptions)
            ->setRequired('media.assign.error.required')
            ->setHtmlAttribute('class', 'form-select');
        $form->addSelect('variantId', 'media.assign.variant', $this->assignVariantOptions)
            ->setRequired()
            ->setHtmlAttribute('class', 'form-select');
        $form->addText('altText', 'media.assign.alt_text')
            ->setMaxLength(255)
            ->setHtmlAttribute('class', 'form-control');
        $form->addInteger('sortOrder', 'media.assign.sort_order')
            ->setDefaultValue(0)
            ->setHtmlAttribute('class', 'form-control');
        $form->addCheckbox('isPrimary', 'media.assign.is_primary');
        $form->addSubmit('submit', 'media.assign.submit')
            ->setHtmlAttribute('class', 'btn btn-success');

        $form->setDefaults([
            'variantId' => $this->assignVariantId ?? 0,
            'sortOrder' => 0,
            'isPrimary' => false,
        ]);
        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            if ($this->assignProductId === null || !$this->catalogPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $mediaAssetValue = $values['mediaAssetId'] ?? null;
            $variantValueRaw = $values['variantId'] ?? null;
            $sortOrderRaw = $values['sortOrder'] ?? null;
            if (
                (!is_string($mediaAssetValue) && !is_int($mediaAssetValue))
                || (!is_string($variantValueRaw) && !is_int($variantValueRaw))
                || (!is_string($sortOrderRaw) && !is_int($sortOrderRaw))
            ) {
                $form->addError($translator->translate('media.assign.error.invalid'));
                return;
            }

            $mediaAssetId = filter_var((string) $mediaAssetValue, FILTER_VALIDATE_INT);
            $variantValue = filter_var((string) $variantValueRaw, FILTER_VALIDATE_INT);
            $sortOrder = filter_var((string) $sortOrderRaw, FILTER_VALIDATE_INT);
            if (!is_int($mediaAssetId) || $mediaAssetId < 1 || !is_int($variantValue) || $variantValue < 0 || !is_int($sortOrder) || $sortOrder < 0) {
                $form->addError($translator->translate('media.assign.error.invalid'));
                return;
            }

            $altText = $values['altText'] ?? null;
            if (!is_string($altText)) {
                $form->addError($translator->translate('media.assign.error.invalid'));
                return;
            }

            $this->productImageWriter->assign(
                $this->assignProductId,
                $variantValue === 0 ? null : $variantValue,
                $mediaAssetId,
                trim($altText) === '' ? null : trim($altText),
                $sortOrder,
                (bool) ($values['isPrimary'] ?? false),
            );

            $this->flashMessage($translator->translate('media.assign.success'), 'success');
            $this->redirect(':Admin:Products:edit', $this->assignProductId);
        };

        return $form;
    }

    protected function createComponentPublicationForm(): Form
    {
        if (!$this->mediaPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );
        $assetOptions = [];
        foreach ($this->libraryAssets as $asset) {
            $assetOptions[$asset->id] = $asset->originalFilename;
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addProtection('media.publication.error.protection');
        $form->addSelect('mediaAssetId', 'media.publication.asset', $assetOptions)
            ->setRequired('media.publication.error.required')
            ->setHtmlAttribute('class', 'form-select');
        $form->addCheckbox('publish', 'media.publication.publish_checkbox');
        $form->addSubmit('submit', 'media.publication.submit')
            ->setHtmlAttribute('class', 'btn btn-outline-primary');
        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            $assetValue = $values['mediaAssetId'] ?? null;
            if ((!is_string($assetValue) && !is_int($assetValue)) || !$this->mediaPermission->canManage()) {
                $form->addError($translator->translate('media.publication.error.invalid'));
                return;
            }

            $assetId = filter_var((string) $assetValue, FILTER_VALIDATE_INT);
            if (!is_int($assetId) || $assetId < 1) {
                $form->addError($translator->translate('media.publication.error.invalid'));
                return;
            }

            if ((bool) ($values['publish'] ?? false)) {
                $this->mediaAssetPublicationWriter->publish($assetId);
                $message = 'media.publication.success';
            } else {
                $this->mediaAssetPublicationWriter->unpublish($assetId);
                $message = 'media.publication.unpublished';
            }

            $this->flashMessage($translator->translate($message), 'success');
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentUploadForm(): Form
    {
        if (!$this->mediaPermission->canManage()) {
            throw new ForbiddenRequestException();
        }

        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addUpload('file', 'media.upload.file')
            ->setRequired('media.upload.error.required')
            ->addRule(Form::MaxFileSize, 'media.upload.error.invalid', 20_000_000)
            ->setHtmlAttribute('accept', 'image/jpeg,image/png,image/webp,image/avif');
        $form->addSubmit('submit', 'media.upload.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, array $values) use ($translator): void {
            $file = $values['file'] ?? null;
            if (!$file instanceof FileUpload || !$file->isOk()) {
                $form->addError($translator->translate('media.upload.error.invalid'));
                return;
            }

            $contents = $file->getContents();
            if (!is_string($contents)) {
                $form->addError($translator->translate('media.upload.error.invalid'));
                return;
            }

            try {
                $this->mediaAssetWriter->upload($contents, $file->getUntrustedName());
            } catch (InvalidArgumentException) {
                $form->addError($translator->translate('media.upload.error.invalid'));
                return;
            }

            $this->flashMessage($translator->translate('media.upload.success'), 'success');
            $this->redirect('default');
        };

        return $form;
    }
}
