<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Modules\Translation\Domain\MessageDomain;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

/**
 * Společný základ presenterů, jejichž šablony používají systémové překlady.
 *
 * Překladová doména je vždy určena konkrétním presenterem. Jazyk se bere
 * z aktivní jazykové routy; presenter bez parametru lang použije výchozí
 * aktivní jazyk z databáze.
 */
abstract class TranslatedPresenter extends Presenter
{
    private TranslatorFactory $translatorFactory;

    private LocaleProvider $localeProvider;


    /**
     * Závislosti se předávají inject metodou, aby potomci mohli používat
     * vlastní konstruktorovou injekci bez řetězení konstruktorů.
     */
    public function injectTranslation(
        TranslatorFactory $translatorFactory,
        LocaleProvider $localeProvider,
    ): void {
        $this->translatorFactory = $translatorFactory;
        $this->localeProvider = $localeProvider;
    }


    protected function beforeRender(): void
    {
        parent::beforeRender();

        $translator = $this->translatorFactory->create(
            $this->getTranslationDomain(),
            $this->resolveTranslationLocale(),
        );

        // Druhý parametr záměrně nepředáváme: překlady musí být načteny
        // za běhu z databáze, nikoliv zapsány do jazykově specifické kompilace.
        $this->template->setTranslator($translator);
    }


    /**
     * Každý presenter výslovně určí vlastníka systémových překladových klíčů.
     */
    abstract protected function getTranslationDomain(): TranslationDomain;


    /**
     * Jazyk z routy musí být stále aktivní; samotná syntaktická platnost nestačí.
     *
     * @throws BadRequestException Pokud je parametr lang neplatný nebo neaktivní.
     */
    private function resolveTranslationLocale(): Locale
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
