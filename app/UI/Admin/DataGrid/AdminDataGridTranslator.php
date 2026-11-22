<?php

declare(strict_types=1);

namespace App\UI\Admin\DataGrid;

use InvalidArgumentException;
use Nette\Localization\Translator;
use Stringable;

/**
 * Překládá interní klíče Ublaboo DataGrid přes databázovou doménu Admin.
 *
 * Ostatní klíče předává překladači konkrétní administrační agendy.
 */
final readonly class AdminDataGridTranslator implements Translator
{
    /** @var array<string, string> */
    private const MESSAGE_KEYS = [
        'contributte_datagrid.no_item_found_reset' => 'admin.datagrid.no_item_found_reset',
        'contributte_datagrid.no_item_found' => 'admin.datagrid.no_item_found',
        'contributte_datagrid.here' => 'admin.datagrid.here',
        'contributte_datagrid.items' => 'admin.datagrid.items',
        'contributte_datagrid.all' => 'admin.datagrid.all',
        'contributte_datagrid.from' => 'admin.datagrid.from',
        'contributte_datagrid.reset_filter' => 'admin.datagrid.reset_filter',
        'contributte_datagrid.group_actions' => 'admin.datagrid.group_actions',
        'contributte_datagrid.show' => 'admin.datagrid.show',
        'contributte_datagrid.add' => 'admin.datagrid.add',
        'contributte_datagrid.edit' => 'admin.datagrid.edit',
        'contributte_datagrid.show_all_columns' => 'admin.datagrid.show_all_columns',
        'contributte_datagrid.show_default_columns' => 'admin.datagrid.show_default_columns',
        'contributte_datagrid.hide_column' => 'admin.datagrid.hide_column',
        'contributte_datagrid.action' => 'admin.datagrid.action',
        'contributte_datagrid.previous' => 'admin.datagrid.previous',
        'contributte_datagrid.next' => 'admin.datagrid.next',
        'contributte_datagrid.choose' => 'admin.datagrid.choose',
        'contributte_datagrid.choose_input_required' => 'admin.datagrid.choose_input_required',
        'contributte_datagrid.execute' => 'admin.datagrid.execute',
        'contributte_datagrid.save' => 'admin.datagrid.save',
        'contributte_datagrid.cancel' => 'admin.datagrid.cancel',
        'contributte_datagrid.multiselect_choose' => 'admin.datagrid.multiselect_choose',
        'contributte_datagrid.multiselect_selected' => 'admin.datagrid.multiselect_selected',
        'contributte_datagrid.filter_submit_button' => 'admin.datagrid.filter_submit_button',
        'contributte_datagrid.show_filter' => 'admin.datagrid.show_filter',
        'contributte_datagrid.per_page_submit' => 'admin.datagrid.per_page_submit',
    ];

    public function __construct(
        private Translator $agendaTranslator,
        private Translator $adminTranslator,
    ) {}

    public function translate(mixed $message, mixed ...$parameters): string
    {
        if (!is_string($message) && !$message instanceof Stringable) {
            throw new InvalidArgumentException('Překladový klíč musí být text nebo Stringable.');
        }

        $message = (string) $message;
        $adminKey = self::MESSAGE_KEYS[$message] ?? null;

        if ($adminKey !== null) {
            return (string) $this->adminTranslator->translate($adminKey, ...$parameters);
        }

        return (string) $this->agendaTranslator->translate($message, ...$parameters);
    }
}
