<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Routers\RouteList;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList();

        $router->addRoute('', static function (IRequest $request, IResponse $response): RedirectResponse {
            $basePath = rtrim($request->getUrl()->getBasePath(), '/');

            return new RedirectResponse($basePath . '/cs', IResponse::S302_Found);
        });

        $router->withModule('Admin')
            ->addRoute('admin', 'Dashboard:default')
            ->addRoute('admin/<presenter>/<action>');

        $router->withModule('Front')
            ->addRoute('<lang cs|en>', 'Home:default')
            ->addRoute('<lang cs|en>/<presenter>/<action>');

        return $router;
    }
}
