<?php

declare(strict_types=1);

namespace App\UI\Admin;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Media\Api\MediaPermission;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationPermission;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Shared authentication boundary and navigation state for protected admin presenters.
 */
trait AdminAccessTrait
{
    private TranslatorFactory $adminTranslatorFactory;
    private LocaleProvider $adminLocaleProvider;
    private CatalogPermission $adminCatalogPermission;
    private MediaPermission $adminMediaPermission;
    private TranslationPermission $adminTranslationPermission;

    public function injectAdminAccess(
        TranslatorFactory $translatorFactory,
        LocaleProvider $localeProvider,
        CatalogPermission $catalogPermission,
        TranslationPermission $translationPermission,
        MediaPermission $mediaPermission,
    ): void {
        $this->adminTranslatorFactory = $translatorFactory;
        $this->adminLocaleProvider = $localeProvider;
        $this->adminCatalogPermission = $catalogPermission;
        $this->adminMediaPermission = $mediaPermission;
        $this->adminTranslationPermission = $translationPermission;
    }

    protected function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Admin:Identity:Login:default');
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->showAdminLogout = true;
        $this->template->adminTranslator = $this->adminTranslatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->adminLocaleProvider->getDefault(),
        );
        $this->template->canViewCatalog = $this->adminCatalogPermission->canView();
        $this->template->canViewMedia = $this->adminMediaPermission->canView();
        $this->template->canViewTranslations = $this->adminTranslationPermission->canView();
        $this->template->adminLocaleCode = $this->adminLocaleProvider->getDefault()->getCode();
    }

    protected function createComponentLogoutForm(): Form
    {
        $translator = $this->adminTranslatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->adminLocaleProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->setHtmlAttribute('class', 'd-flex');
        $form->addSubmit('logout', 'identity.logout.submit')
            ->setHtmlAttribute('class', 'btn btn-outline-secondary btn-sm');

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->getUser()->logout(true);
            $this->getSession()->destroy();
            $this->redirect(':Admin:Identity:Login:default');
        };

        return $form;
    }
}
