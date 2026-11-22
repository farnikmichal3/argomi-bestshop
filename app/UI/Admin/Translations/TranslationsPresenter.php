<?php

declare(strict_types=1);

namespace App\UI\Admin\Translations;

use App\Modules\Translation\Api\CustomMessageNotFoundException;
use App\Modules\Translation\Api\DefaultLanguageCannotBeDeactivatedException;
use App\Modules\Translation\Api\LanguageNotFoundException;
use App\Modules\Translation\Api\LocaleManager;
use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\MessageTranslationWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\UI\Admin\AdminAccessTrait;
use App\Modules\Translation\Api\TranslationMessageStatus;
use App\Modules\Translation\Api\TranslationPermission;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use stdClass;

final class TranslationsPresenter extends TranslatedPresenter
{
    use AdminAccessTrait;

    public function __construct(
        private readonly MessageTranslationReader $translationReader,
        private readonly MessageTranslationWriter $translationWriter,
        private readonly LocaleManager $localeManager,
        private readonly TranslatorFactory $translatorFactory,
        private readonly TranslationPermission $translationPermission,
        private readonly LocaleProvider $localeProvider,
    ) {
        parent::__construct();
    }

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Admin;
    }

    public function renderDefault(
        ?string $domain = null,
        ?string $locale = null,
        ?string $status = null,
    ): void {
        if (!$this->translationPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $selectedDomain = $domain === null || $domain === ''
            ? null
            : TranslationDomain::tryFrom($domain);

        if ($domain !== null && $domain !== '' && $selectedDomain === null) {
            throw new BadRequestException('Neplatný filtr domény.');
        }

        $selectedLocale = null;

        if ($locale !== null && $locale !== '') {
            try {
                $selectedLocale = Locale::fromString($locale);
            } catch (InvalidLocaleException $exception) {
                throw new BadRequestException('Neplatný filtr jazyka.', previous: $exception);
            }

            if (!$this->localeProvider->isActive($selectedLocale)) {
                throw new BadRequestException('Vybraný jazyk není aktivní.');
            }
        }

        $selectedStatus = $status === null || $status === ''
            ? null
            : TranslationMessageStatus::tryFrom($status);

        if ($status !== null && $status !== '' && $selectedStatus === null) {
            throw new BadRequestException('Neplatný filtr stavu.');
        }

        $this->template->canManage = $this->translationPermission->canManage();
        $this->template->rows = $this->translationReader->search(
            $selectedDomain,
            $selectedLocale,
            $selectedStatus,
        );
        $this->template->domains = TranslationDomain::cases();
        $this->template->locales = $this->localeProvider->getActiveLocales();
        $this->template->managedLocales = $this->localeManager->getAll();
        $this->template->statuses = TranslationMessageStatus::cases();
        $this->template->selectedDomain = $selectedDomain->value ?? '';
        $this->template->selectedLocale = $selectedLocale?->getCode() ?? '';
        $this->template->selectedStatus = $selectedStatus->value ?? '';
    }

    protected function createComponentManageForm(): Form
    {
        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );

        $domains = [];

        foreach (TranslationDomain::cases() as $domain) {
            $domains[$domain->value] = $domain->value;
        }

        $locales = [];

        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $locales[$locale->getCode()] = $locale->getCode();
        }

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('operation', 'translations.form.operation', [
            'create' => 'translations.operation.create',
            'update' => 'translations.operation.update',
            'delete' => 'translations.operation.delete',
        ])->setHtmlAttribute('class', 'form-select')->setDefaultValue('create')->setRequired();
        $form->addSelect('domain', 'translations.create.domain', $domains)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired();
        $form->addText('messageKey', 'translations.create.key')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired();
        $form->addSelect('locale', 'translations.create.locale', $locales)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired();
        $form->addTextArea('messageText', 'translations.create.text')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 3);
        $form->addSubmit('submit', 'translations.form.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator): void {
            if (!$this->translationPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $domainValue = $values->domain;
            $localeValue = $values->locale;
            $keyValue = $values->messageKey;
            $textValue = $values->messageText;
            $operationValue = $values->operation;

            $domain = is_string($domainValue) ? TranslationDomain::tryFrom($domainValue) : null;

            if ($domain === null || !is_string($localeValue)) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            try {
                $locale = Locale::fromString($localeValue);
            } catch (InvalidLocaleException) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            if (!$this->localeProvider->isActive($locale) || !is_string($keyValue) || !is_string($operationValue)) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            $messageText = is_string($textValue) ? $textValue : '';

            try {
                switch ($operationValue) {
                    case 'create':
                        $this->translationWriter->createCustom($domain, $keyValue, $locale, $messageText);
                        $flashKey = 'translations.created';
                        break;

                    case 'update':
                        $this->translationWriter->updateCustom($domain, $keyValue, $locale, $messageText);
                        $flashKey = 'translations.updated';
                        break;

                    case 'delete':
                        $this->translationWriter->deleteCustom($domain, $keyValue, $locale);
                        $flashKey = 'translations.deleted';
                        break;

                    default:
                        $form->addError($translator->translate('translations.error.invalid_input'));
                        return;
                }
            } catch (CustomMessageNotFoundException) {
                $form->addError($translator->translate('translations.error.not_found'));
                return;
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('translations.error.duplicate'));
                return;
            } catch (\InvalidArgumentException) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            $this->flashMessage($translator->translate($flashKey), 'success');
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentLanguageForm(): Form
    {
        $translator = $this->translatorFactory->createTranslator(
            TranslationDomain::Admin,
            $this->localeProvider->getDefault(),
        );

        $form = new Form();
        $form->setTranslator($translator);
        $form->addSelect('operation', 'translations.languages.operation', [
            'create' => 'translations.languages.operation.create',
            'activate' => 'translations.languages.operation.activate',
            'deactivate' => 'translations.languages.operation.deactivate',
        ])->setHtmlAttribute('class', 'form-select')->setDefaultValue('create')->setRequired();
        $form->addText('code', 'translations.languages.code')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired();
        $form->addText('name', 'translations.languages.name')
            ->setHtmlAttribute('class', 'form-control');
        $form->addText('sortOrder', 'translations.languages.sort_order')
            ->setHtmlAttribute('class', 'form-control')
            ->setDefaultValue('0');
        $form->addSubmit('submit', 'translations.languages.submit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($translator): void {
            if (!$this->translationPermission->canManage()) {
                throw new ForbiddenRequestException();
            }

            $code = $values->code;
            $name = $values->name;
            $sortOrderValue = $values->sortOrder;
            $operation = $values->operation;

            if (!is_string($code) || !is_string($operation)) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            try {
                $locale = Locale::fromString($code);
            } catch (InvalidLocaleException) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            try {
                switch ($operation) {
                    case 'create':
                        $sortOrder = is_string($sortOrderValue) ? filter_var($sortOrderValue, FILTER_VALIDATE_INT) : false;

                        if (!is_string($name) || !is_int($sortOrder) || $sortOrder < 0) {
                            $form->addError($translator->translate('translations.error.invalid_input'));
                            return;
                        }

                        $this->localeManager->add($locale, $name, $sortOrder);
                        $flashKey = 'translations.languages.created';
                        break;

                    case 'activate':
                        $this->localeManager->setActive($locale, true);
                        $flashKey = 'translations.languages.activated';
                        break;

                    case 'deactivate':
                        $this->localeManager->setActive($locale, false);
                        $flashKey = 'translations.languages.deactivated';
                        break;

                    default:
                        $form->addError($translator->translate('translations.error.invalid_input'));
                        return;
                }
            } catch (DefaultLanguageCannotBeDeactivatedException) {
                $form->addError($translator->translate('translations.languages.error.default'));
                return;
            } catch (LanguageNotFoundException) {
                $form->addError($translator->translate('translations.languages.error.not_found'));
                return;
            } catch (UniqueConstraintViolationException) {
                $form->addError($translator->translate('translations.languages.error.duplicate'));
                return;
            } catch (\InvalidArgumentException) {
                $form->addError($translator->translate('translations.error.invalid_input'));
                return;
            }

            $this->flashMessage($translator->translate($flashKey), 'success');
            $this->redirect('default');
        };

        return $form;
    }
}
