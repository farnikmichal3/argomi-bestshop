<?php

declare(strict_types=1);

namespace App\UI\Front\Media;

use App\Modules\Media\Api\MediaPublicAssetReader;
use App\Presentation\TranslatedPresenter;
use App\Modules\Translation\Api\TranslationDomain;
use Nette\Application\BadRequestException;

final class MediaPresenter extends TranslatedPresenter
{
    public function __construct(private readonly MediaPublicAssetReader $mediaReader)
    {
        parent::__construct();
    }

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Catalog;
    }

    public function actionDefault(string $token, string $variant = 'detail'): void
    {
        if (!in_array($variant, ['thumbnail', 'listing', 'detail'], true)) {
            throw new BadRequestException('Varianta veřejného média není platná.');
        }

        $image = $this->mediaReader->read($token, $variant);
        if ($image === null) {
            throw new BadRequestException('Veřejné médium nebylo nalezeno.');
        }

        $this->sendResponse(new PublicMediaResponse($image->contents, $image->mimeType));
    }
}
