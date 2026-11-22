<?php

declare(strict_types=1);

namespace App\UI\Admin\DataGrid;

use Contributte\Datagrid\Datagrid;
use Contributte\Datagrid\DataSource\IDataSource;
use Nette\ComponentModel\IContainer;
use Nette\Localization\Translator;

/**
 * Společná továrna administračních tabulek.
 *
 * Konkrétní moduly předávají pouze datový zdroj a překladač. Závislost
 * na Ublaboo DataGrid tak zůstává izolována v této administrační infrastruktuře.
 */
final class AdminDataGridFactory
{
    public function create(
        IContainer $parent,
        string $name,
        IDataSource $dataSource,
        Translator $translator,
    ): Datagrid {
        // Filtry katalogu nejsou citlivé; GET umožní sdílet a obnovit jejich stav.
        Datagrid::$formMethod = 'GET';

        $grid = new Datagrid($parent, $name);
        $grid->setPrimaryKey('id');
        $grid->setDataSource($dataSource);
        $grid->setDefaultPerPage(12);
        $grid->setItemsPerPageList([12, 24, 48], false);
        $grid->setRememberState(false);
        $grid->setAutoSubmit(false);
        $grid->setOuterFilterRendering(true);
        $grid->setTranslator($translator);

        return $grid;
    }
}
