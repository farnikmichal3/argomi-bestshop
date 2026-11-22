-- ArgoMi BestShop · Admin DataGrid system messages (cs/en)
-- Idempotentní seed; existující systémové i vlastní překlady zůstávají beze změny.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('admin.datagrid.no_item_found_reset', 'cs', 'Žádné položky nebyly nalezeny. Filtr můžeš obnovit.', 'core', 0, NULL, 0),
    ('admin.datagrid.no_item_found_reset', 'en', 'No items found. You can reset the filter.', 'core', 0, NULL, 0),
    ('admin.datagrid.no_item_found', 'cs', 'Nebyly nalezeny žádné položky.', 'core', 0, NULL, 0),
    ('admin.datagrid.no_item_found', 'en', 'No items found.', 'core', 0, NULL, 0),
    ('admin.datagrid.here', 'cs', 'zde', 'core', 0, NULL, 0),
    ('admin.datagrid.here', 'en', 'here', 'core', 0, NULL, 0),
    ('admin.datagrid.items', 'cs', 'Položky', 'core', 0, NULL, 0),
    ('admin.datagrid.items', 'en', 'Items', 'core', 0, NULL, 0),
    ('admin.datagrid.all', 'cs', 'všechny', 'core', 0, NULL, 0),
    ('admin.datagrid.all', 'en', 'all', 'core', 0, NULL, 0),
    ('admin.datagrid.from', 'cs', 'z', 'core', 0, NULL, 0),
    ('admin.datagrid.from', 'en', 'of', 'core', 0, NULL, 0),
    ('admin.datagrid.reset_filter', 'cs', 'Zrušit filtr', 'core', 0, NULL, 0),
    ('admin.datagrid.reset_filter', 'en', 'Reset filter', 'core', 0, NULL, 0),
    ('admin.datagrid.group_actions', 'cs', 'Hromadné akce', 'core', 0, NULL, 0),
    ('admin.datagrid.group_actions', 'en', 'Group actions', 'core', 0, NULL, 0),
    ('admin.datagrid.show', 'cs', 'Zobrazit', 'core', 0, NULL, 0),
    ('admin.datagrid.show', 'en', 'Show', 'core', 0, NULL, 0),
    ('admin.datagrid.add', 'cs', 'Přidat', 'core', 0, NULL, 0),
    ('admin.datagrid.add', 'en', 'Add', 'core', 0, NULL, 0),
    ('admin.datagrid.edit', 'cs', 'Upravit', 'core', 0, NULL, 0),
    ('admin.datagrid.edit', 'en', 'Edit', 'core', 0, NULL, 0),
    ('admin.datagrid.show_all_columns', 'cs', 'Zobrazit všechny sloupce', 'core', 0, NULL, 0),
    ('admin.datagrid.show_all_columns', 'en', 'Show all columns', 'core', 0, NULL, 0),
    ('admin.datagrid.show_default_columns', 'cs', 'Zobrazit výchozí sloupce', 'core', 0, NULL, 0),
    ('admin.datagrid.show_default_columns', 'en', 'Show default columns', 'core', 0, NULL, 0),
    ('admin.datagrid.hide_column', 'cs', 'Skrýt sloupec', 'core', 0, NULL, 0),
    ('admin.datagrid.hide_column', 'en', 'Hide column', 'core', 0, NULL, 0),
    ('admin.datagrid.action', 'cs', 'Akce', 'core', 0, NULL, 0),
    ('admin.datagrid.action', 'en', 'Action', 'core', 0, NULL, 0),
    ('admin.datagrid.previous', 'cs', 'Předchozí', 'core', 0, NULL, 0),
    ('admin.datagrid.previous', 'en', 'Previous', 'core', 0, NULL, 0),
    ('admin.datagrid.next', 'cs', 'Další', 'core', 0, NULL, 0),
    ('admin.datagrid.next', 'en', 'Next', 'core', 0, NULL, 0),
    ('admin.datagrid.choose', 'cs', 'Vyber', 'core', 0, NULL, 0),
    ('admin.datagrid.choose', 'en', 'Choose', 'core', 0, NULL, 0),
    ('admin.datagrid.choose_input_required', 'cs', 'Text hromadné akce nesmí být prázdný.', 'core', 0, NULL, 0),
    ('admin.datagrid.choose_input_required', 'en', 'Group action text must not be empty.', 'core', 0, NULL, 0),
    ('admin.datagrid.execute', 'cs', 'Provést', 'core', 0, NULL, 0),
    ('admin.datagrid.execute', 'en', 'Execute', 'core', 0, NULL, 0),
    ('admin.datagrid.save', 'cs', 'Uložit', 'core', 0, NULL, 0),
    ('admin.datagrid.save', 'en', 'Save', 'core', 0, NULL, 0),
    ('admin.datagrid.cancel', 'cs', 'Zrušit', 'core', 0, NULL, 0),
    ('admin.datagrid.cancel', 'en', 'Cancel', 'core', 0, NULL, 0),
    ('admin.datagrid.multiselect_choose', 'cs', 'Vyber', 'core', 0, NULL, 0),
    ('admin.datagrid.multiselect_choose', 'en', 'Choose', 'core', 0, NULL, 0),
    ('admin.datagrid.multiselect_selected', 'cs', 'Vybráno: {0}', 'core', 0, NULL, 0),
    ('admin.datagrid.multiselect_selected', 'en', 'Selected: {0}', 'core', 0, NULL, 0),
    ('admin.datagrid.filter_submit_button', 'cs', 'Filtrovat', 'core', 0, NULL, 0),
    ('admin.datagrid.filter_submit_button', 'en', 'Filter', 'core', 0, NULL, 0),
    ('admin.datagrid.show_filter', 'cs', 'Zobrazit filtr', 'core', 0, NULL, 0),
    ('admin.datagrid.show_filter', 'en', 'Show filter', 'core', 0, NULL, 0),
    ('admin.datagrid.per_page_submit', 'cs', 'Změnit', 'core', 0, NULL, 0),
    ('admin.datagrid.per_page_submit', 'en', 'Change', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
