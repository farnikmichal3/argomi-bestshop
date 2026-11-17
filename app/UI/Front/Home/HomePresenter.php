<?php

declare(strict_types=1);

namespace App\UI\Front\Home;

use App\Modules\Translation\Domain\MessageDomain;
use App\Presentation\TranslatedPresenter;

final class HomePresenter extends TranslatedPresenter
{
    protected function getTranslationDomain(): MessageDomain
    {
        return MessageDomain::Catalog;
    }
}
