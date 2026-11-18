<?php

declare(strict_types=1);

namespace App\UI\Admin\Translations;

use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationMessageStatus;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;

final class TranslationsPresenter extends TranslatedPresenter
{
    public function __construct(
        private readonly MessageTranslationReader $translationReader,
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

        $this->template->rows = $this->translationReader->search(
            $selectedDomain,
            $selectedLocale,
            $selectedStatus,
        );
        $this->template->domains = TranslationDomain::cases();
        $this->template->locales = $this->localeProvider->getActiveLocales();
        $this->template->statuses = TranslationMessageStatus::cases();
        $this->template->selectedDomain = $selectedDomain->value ?? '';
        $this->template->selectedLocale = $selectedLocale?->getCode() ?? '';
        $this->template->selectedStatus = $selectedStatus->value ?? '';
    }
}
