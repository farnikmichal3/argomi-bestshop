import './shared.js';
import 'admin-lte';

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
