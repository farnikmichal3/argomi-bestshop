import './shared.js';
import 'admin-lte';
import hugerte from 'hugerte';
import 'hugerte/models/dom';
import 'hugerte/themes/silver';
import 'hugerte/icons/default';
import 'hugerte/skins/ui/oxide/skin.js';
import 'hugerte/plugins/lists';
import 'hugerte/plugins/link';
import 'hugerte/plugins/image';
import 'hugerte/plugins/table';
import 'hugerte/plugins/code';
import 'hugerte/plugins/wordcount';

document.querySelectorAll('textarea[data-hugerte]').forEach((textarea) => {
    hugerte.init({
        target: textarea,
        height: 420,
        menubar: 'edit insert format table',
        plugins: 'lists link image table code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | removeformat | code',
        toolbar_mode: 'sliding',
        file_picker_types: 'image',
        file_picker_callback: async (callback, value, meta) => {
            if (meta.filetype !== 'image') {
                return;
            }

            const pickerUrl = textarea.dataset.mediaPickerUrl;
            if (!pickerUrl) {
                editor.windowManager.alert('Knihovna publikovaných médií není dostupná.');
                return;
            }

            try {
                const response = await fetch(pickerUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Media picker request failed.');
                }

                const assets = await response.json();
                if (!Array.isArray(assets) || assets.length === 0) {
                    editor.windowManager.alert('Knihovna neobsahuje žádné publikované obrázky.');
                    return;
                }

                const options = assets
                    .filter((asset) => typeof asset.value === 'string' && typeof asset.title === 'string')
                    .map((asset) => ({ value: asset.value, text: asset.title }));
                if (options.length === 0) {
                    editor.windowManager.alert('Knihovna neobsahuje žádné publikované obrázky.');
                    return;
                }

                editor.windowManager.open({
                    title: 'Vybrat publikovaný obrázek',
                    size: 'large',
                    body: {
                        type: 'panel',
                        items: [{
                            type: 'selectbox',
                            name: 'mediaAsset',
                            label: 'Obrázek',
                            items: options,
                        }],
                    },
                    buttons: [
                        { type: 'cancel', text: 'Zrušit' },
                        { type: 'submit', text: 'Vložit', primary: true },
                    ],
                    onSubmit: (api) => {
                        const selected = assets.find((asset) => asset.value === api.getData().mediaAsset);
                        if (selected) {
                            callback(selected.value, {
                                alt: selected.alt ?? selected.title,
                                title: selected.title,
                            });
                        }
                        api.close();
                    },
                });
            } catch {
                editor.windowManager.alert('Obrázky se nepodařilo načíst. Zkontroluj oprávnění a zkus to znovu.');
            }
        },
        branding: false,
        promotion: false,
        statusbar: true,
        resize: true,
        content_style: 'body { font-family: system-ui, sans-serif; font-size: 15px; line-height: 1.6; }',
        setup: (editor) => {
            const form = textarea.form;
            if (form) {
                form.addEventListener('submit', () => {
                    editor.save();
                });
            }
        },
    });
});

document.querySelectorAll('form[data-product-image-management]').forEach((form) => {
    const imageSelect = form.elements.namedItem('imageId');
    const altTextInput = form.elements.namedItem('altText');
    const sortOrderInput = form.elements.namedItem('sortOrder');

    if (!(imageSelect instanceof HTMLSelectElement)
        || !(altTextInput instanceof HTMLInputElement)
        || !(sortOrderInput instanceof HTMLInputElement)) {
        return;
    }

    let imageConfiguration;
    try {
        imageConfiguration = JSON.parse(form.dataset.imageConfiguration ?? '{}');
    } catch {
        return;
    }

    imageSelect.addEventListener('change', () => {
        const selectedImage = imageConfiguration[imageSelect.value];
        if (!selectedImage) {
            return;
        }

        altTextInput.value = selectedImage.altText;
        sortOrderInput.value = String(selectedImage.sortOrder);
        altTextInput.dispatchEvent(new Event('input', { bubbles: true }));
        sortOrderInput.dispatchEvent(new Event('input', { bubbles: true }));
    });
});

document.querySelectorAll('tbody[data-category-sortable]').forEach((group) => {
    let draggedRow = null;

    const updateOrder = () => {
        const formId = group.dataset.formId;
        const form = formId ? document.getElementById(formId) : null;
        const input = form?.querySelector('input[name="orderedCategoryIds"]');
        if (!input) {
            return;
        }

        const ids = Array.from(group.querySelectorAll('tr[data-category-id]'))
            .map((row) => Number(row.dataset.categoryId));
        input.value = JSON.stringify(ids);
    };

    group.addEventListener('dragstart', (event) => {
        const row = event.target.closest('tr[data-category-id]');
        if (!row) {
            return;
        }

        draggedRow = row;
        row.classList.add('table-active');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.categoryId);
    });

    group.addEventListener('dragover', (event) => {
        if (!draggedRow) {
            return;
        }

        const targetRow = event.target.closest('tr[data-category-id]');
        if (!targetRow || targetRow === draggedRow) {
            return;
        }

        event.preventDefault();
        const bounds = targetRow.getBoundingClientRect();
        const insertAfter = event.clientY > bounds.top + bounds.height / 2;
        group.insertBefore(draggedRow, insertAfter ? targetRow.nextSibling : targetRow);
    });

    group.addEventListener('drop', (event) => {
        if (!draggedRow) {
            return;
        }

        event.preventDefault();
        updateOrder();
    });

    group.addEventListener('dragend', () => {
        if (draggedRow) {
            draggedRow.classList.remove('table-active');
        }
        draggedRow = null;
        updateOrder();
    });
});
