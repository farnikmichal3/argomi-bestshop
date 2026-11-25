<?php

declare(strict_types=1);

namespace App\UI\Admin\Media;

use App\Modules\Media\Api\MediaAssetContentReader;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Modules\Media\Api\MediaPermission;
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

    public function __construct(
        private readonly MediaPermission $mediaPermission,
        private readonly MediaAssetReader $mediaAssetReader,
        private readonly MediaAssetWriter $mediaAssetWriter,
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

    public function renderDefault(): void
    {
        if (!$this->mediaPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $this->template->assets = $this->mediaAssetReader->findLibrary(48, 0);
        $this->template->canManageMedia = $this->mediaPermission->canManage();
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
            ->addRule(Form::MAX_FILE_SIZE, 'media.upload.error.invalid', 20_000_000)
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
