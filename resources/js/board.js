function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

function closestTaskCard(el) {
    return el?.closest?.('[data-task-id]') ?? null;
}

function setupKanban(root) {
    const updateUrl = root.getAttribute('data-update-url');
    if (!updateUrl) return;

    let draggingTaskId = null;
    let recentlyDragged = false;
    let pointerDown = null;

    function serializeColumns() {
        const result = {};
        root.querySelectorAll('[data-column-status]').forEach((col) => {
            const status = col.getAttribute('data-column-status');
            const tasks = Array.from(col.querySelectorAll('[data-task-id]')).map((t) => Number(t.getAttribute('data-task-id')));
            result[status] = tasks;
        });
        return result;
    }

    async function persist() {
        const payload = { columns: serializeColumns() };

        await fetch(updateUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
    }

    root.addEventListener('dragstart', (e) => {
        const card = closestTaskCard(e.target);
        if (!card) return;

        draggingTaskId = card.getAttribute('data-task-id');
        recentlyDragged = true;
        card.classList.add('opacity-60');

        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(draggingTaskId));
        }
    });

    root.addEventListener('dragend', (e) => {
        const card = closestTaskCard(e.target);
        if (!card) return;
        card.classList.remove('opacity-60');
        draggingTaskId = null;
        window.setTimeout(() => {
            recentlyDragged = false;
        }, 100);
    });

    root.addEventListener('pointerdown', (e) => {
        if (e.button !== 0) return;
        const card = closestTaskCard(e.target);
        if (!card) return;

        pointerDown = {
            id: card.getAttribute('data-task-id'),
            url: card.getAttribute('data-open-url') ?? '',
            x: e.clientX,
            y: e.clientY,
            moved: false,
        };
    });

    root.addEventListener('pointermove', (e) => {
        if (!pointerDown) return;
        const dx = Math.abs(e.clientX - pointerDown.x);
        const dy = Math.abs(e.clientY - pointerDown.y);
        if (dx > 6 || dy > 6) pointerDown.moved = true;
    });

    root.addEventListener('pointerup', (e) => {
        const pd = pointerDown;
        pointerDown = null;
        if (!pd) return;

        if (recentlyDragged) return;
        if (pd.moved) return;
        if (e.button !== 0) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const target = e.target;
        if (target && target.closest && target.closest('a,button,input,select,textarea')) return;
        if (!pd.url) return;

        window.location.href = pd.url;
    });

    root.addEventListener('keydown', (e) => {
        const card = closestTaskCard(e.target);
        if (!card) return;
        if (e.key !== 'Enter' && e.key !== ' ') return;

        const url = card.getAttribute('data-open-url') ?? '';
        if (!url) return;
        e.preventDefault();
        window.location.href = url;
    });

    root.querySelectorAll('[data-column-status]').forEach((col) => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';

            const overCard = closestTaskCard(e.target);
            const draggingCard = draggingTaskId ? root.querySelector(`[data-task-id="${draggingTaskId}"]`) : null;
            if (!draggingCard) return;

            if (overCard && overCard !== draggingCard && overCard.parentElement === col) {
                const rect = overCard.getBoundingClientRect();
                const shouldInsertBefore = e.clientY < rect.top + rect.height / 2;
                col.insertBefore(draggingCard, shouldInsertBefore ? overCard : overCard.nextSibling);
            } else if (!overCard) {
                col.appendChild(draggingCard);
            }
        });

        col.addEventListener('drop', async (e) => {
            e.preventDefault();

            try {
                await persist();
            } catch {
                // If the request fails, the UI is still updated; user can refresh.
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-kanban-board]').forEach(setupKanban);
});
