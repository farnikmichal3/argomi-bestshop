<?php

declare(strict_types=1);

namespace App\UI\Admin;

use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use stdClass;

/**
 * Shared authentication boundary for every protected admin presenter.
 */
trait AdminAccessTrait
{
    private TranslatorFactory $adminTranslatorFactory;
    private LocaleProvider $adminLocaleProvider;

    public function injectAdminAccess(
        TranslatorFactory $translatorFactory,
        LocaleProvider $localeProvider,
    ): void {
        $this->adminTranslatorFactory = $translatorFactory;
        $this->adminLocaleProvider = $localeProvider;
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
    }

    protected function createComponentLogoutForm(): Form
    {
        $translator = $this->adminTranslatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->adminLocaleProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addProtection('identity.logout.expired');
        $form->addSubmit('logout', 'identity.logout.submit');

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->getUser()->logout(true);
            $this->getSession()->destroy();
            $this->redirect(':Admin:Identity:Login:default');
        };

        return $form;
    }
}
