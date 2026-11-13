<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use LogicException;
use Nette;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Routers\RouteList;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(LocaleProvider $localeProvider): RouteList
    {
        $defaultLocale = $localeProvider->getDefault();
        $activeLocales = $localeProvider->getActiveLocales();

        if ($activeLocales === []) {
            // Bez aktivního jazyka nelze bezpečně sestavit veřejné jazykové routy.
            throw new LogicException('Pro routování není dostupný žádný aktivní jazyk.');
        }

        $languageCodes = array_map(
            static fn (Locale $locale): string => $locale->getCode(),
            $activeLocales,
        );
        $languagePattern = implode('|', $languageCodes);
        $defaultLocaleCode = $defaultLocale->getCode();

        $router = new RouteList();

        $router->addRoute('', static function (IRequest $request, IResponse $response) use ($defaultLocaleCode): RedirectResponse {
            $basePath = rtrim($request->getUrl()->getBasePath(), '/');

            return new RedirectResponse($basePath . '/' . $defaultLocaleCode, IResponse::S302_Found);
        });

        $router->withModule('Admin')
            ->addRoute('admin', 'Dashboard:default')
            ->addRoute('admin/<presenter>/<action>');

        // Povolené jazykové prefixy se vždy sestavují z aktivních záznamů v databázi.
        $router->withModule('Front')
            ->addRoute('<lang ' . $languagePattern . '>', 'Home:default')
            ->addRoute('<lang ' . $languagePattern . '>/<presenter>/<action>');

        return $router;
    }
}
