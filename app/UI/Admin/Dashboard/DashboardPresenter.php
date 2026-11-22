<?php

declare(strict_types=1);

namespace App\UI\Admin\Dashboard;

use App\UI\Admin\AdminAccessTrait;
use Nette\Application\UI\Presenter;

final class DashboardPresenter extends Presenter
{
    use AdminAccessTrait;
}
