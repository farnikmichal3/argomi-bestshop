import './shared.js';
import 'admin-lte';
import hugerte from 'hugerte';
import 'hugerte/models/dom';
import 'hugerte/themes/silver';
import 'hugerte/icons/default';
import 'hugerte/skins/ui/oxide/skin.js';
import 'hugerte/plugins/lists';
import 'hugerte/plugins/link';
import 'hugerte/plugins/table';
import 'hugerte/plugins/code';
import 'hugerte/plugins/wordcount';

document.querySelectorAll('textarea[data-hugerte]').forEach((textarea) => {
    hugerte.init({
        target: textarea,
        height: 420,
        menubar: 'edit insert format table',
        plugins: 'lists link table code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
        toolbar_mode: 'sliding',
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
