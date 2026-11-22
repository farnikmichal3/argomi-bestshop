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
            static fn(Locale $locale): string => $locale->getCode(),
            $activeLocales,
        );
        $languagePattern = implode('|', $languageCodes);
        $defaultLocaleCode = $defaultLocale->getCode();

        $router = new RouteList();

        $router->addRoute('', static function (IRequest $request, IResponse $response) use ($defaultLocaleCode): RedirectResponse {
            $basePath = rtrim($request->getUrl()->getBasePath(), '/');

            return new RedirectResponse($basePath . '/' . $defaultLocaleCode, IResponse::S302_Found);
        });

        $adminRouter = $router->withModule('Admin');

        $adminRouter
            ->addRoute('admin/login', 'Identity:Login:default')
            ->addRoute('admin', 'Dashboard:default')
            // Diagnostická stránka je navíc chráněna přímo v presenteru kontrolou Tracy debug režimu.
            ->addRoute('admin/development/sql-diagnostics', 'Development:SqlDiagnostics:default')
            // Vnořené presentery musí být před obecnými admin routami, jinak se druhý segment
            // chybně vyhodnotí jako akce jednosegmentového presenteru (např. Admin:Catalog).
            ->addRoute(
                'admin/catalog/categories[/<action>][/<id \\d+>]',
                'Catalog:Categories:default',
            )
            // Jednosegmentové admin stránky používají výchozí akci presenteru.
            ->addRoute('admin/<presenter>', [
                'action' => 'default',
            ])
            ->addRoute('admin/<presenter>/<action>');

        // Povolené jazykové prefixy se vždy sestavují z aktivních záznamů v databázi.
        $router->withModule('Front')
            ->addRoute('<lang ' . $languagePattern . '>', 'Home:default')
            ->addRoute('<lang ' . $languagePattern . '>/<presenter>/<action>');

        return $router;
    }
}
