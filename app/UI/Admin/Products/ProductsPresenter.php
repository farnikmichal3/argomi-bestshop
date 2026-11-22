<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Translation\Api\TranslationDomain;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Control;

final class ProductsPresenter extends TranslatedPresenter
{
    public function __construct(
        private readonly ProductAdminDataGridFactory $productAdminDataGridFactory,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
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

    protected function createComponentProductsGrid(string $name): Control
    {
        if (!$this->catalogPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        return $this->productAdminDataGridFactory->create(
            $this,
            $name,
            $this->resolveLocale(),
        );
    }

    private function resolveLocale(): Locale
    {
        $language = $this->getParameter('lang');

        if ($language === null) {
            return $this->localeProvider->getDefault();
        }

        if (!is_string($language)) {
            throw new BadRequestException('Neplatný jazyk požadavku.');
        }

        try {
            $locale = Locale::fromString($language);
        } catch (InvalidLocaleException $exception) {
            throw new BadRequestException('Neplatný jazyk požadavku.', previous: $exception);
        }

        if (!$this->localeProvider->isActive($locale)) {
            throw new BadRequestException('Požadovaný jazyk není aktivní.');
        }

        return $locale;
    }
}
