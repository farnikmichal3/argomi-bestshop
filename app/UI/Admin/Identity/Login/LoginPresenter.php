<?php

declare(strict_types=1);

namespace App\UI\Admin\Identity\Login;

use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;
use stdClass;

final class LoginPresenter extends Presenter
{
    public function __construct(
        private readonly TranslatorFactory $translatorFactory,
        private readonly LocaleProvider $localeProvider,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
        $this->setLayout(false);

        if ($this->getUser()->isLoggedIn()) {
            $this->redirect(':Admin:Dashboard:default');
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $locale = $this->localeProvider->getDefault();
        $this->template->locale = $locale->getCode();
        $this->template->setTranslator(
            $this->translatorFactory->createTranslator(TranslationDomain::Admin, $locale),
        );
    }

    protected function createComponentLoginForm(): Form
    {
        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addEmail('email', 'identity.login.email')
            ->setHtmlAttribute('autocomplete', 'username')
            ->setRequired('identity.login.required');
        $form->addPassword('password', 'identity.login.password')
            ->setHtmlAttribute('autocomplete', 'current-password')
            ->setRequired('identity.login.required');
        $form->addSubmit('submit', 'identity.login.submit');

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator): void {
            if (!is_string($values->email) || !is_string($values->password)) {
                $form->addError($translator->translate('identity.login.invalid'));
                return;
            }

            try {
                // Nette SessionStorage regenerates the session ID when it persists the authenticated identity.
                $this->getUser()->login(mb_strtolower(trim($values->email)), $values->password);
                $this->getUser()->setExpiration('30 minutes', true);
            } catch (AuthenticationException) {
                $form->addError($translator->translate('identity.login.invalid'));
                return;
            }

            $this->redirect(':Admin:Dashboard:default');
        };

        return $form;
    }
}
