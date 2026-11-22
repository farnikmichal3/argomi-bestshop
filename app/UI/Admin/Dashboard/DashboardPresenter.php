<?php

declare(strict_types=1);

namespace App\UI\Admin\Dashboard;

use App\Modules\Translation\Api\TranslationDomain;
use App\Presentation\TranslatedPresenter;
use App\UI\Admin\AdminAccessTrait;

final class DashboardPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Admin;
    }
}
