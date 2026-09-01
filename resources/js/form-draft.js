/**
 * Keeps what has been typed into a long form, so closing the tab or losing the
 * connection part-way through does not throw the work away.
 *
 * The server already stores each wizard step once it is submitted; the gap this
 * fills is the step in progress, which is where the typing actually is.
 *
 * The draft lives in this browser only, never leaves the device, and is deleted
 * the moment the step is submitted. Because a registration form holds personal
 * details, each form also carries a visible note and a button to clear it, for
 * anyone filling it in on a shared computer.
 */

const PREFIX = 'ppdb:draft:';

/** Values that must never be written to disk, whatever form they appear in. */
const skipped = (field) =>
    !field.name
    || field.disabled
    || field.type === 'file'
    || field.type === 'password'
    || field.type === 'hidden'
    || field.name === '_token'
    || field.name === '_method'
    || field.hasAttribute('data-no-draft');

const readable = () => {
    try {
        // Private windows and locked-down browsers throw on first access.
        const probe = '__ppdb_probe__';
        window.localStorage.setItem(probe, '1');
        window.localStorage.removeItem(probe);

        return true;
    } catch {
        return false;
    }
};

if (readable()) {
    document.querySelectorAll('form[data-draft]').forEach((form) => {
        const key = PREFIX + (form.dataset.draft || window.location.pathname);
        const note = form.querySelector('[data-draft-note]');
        const clearButton = form.querySelector('[data-draft-clear]');

        const fields = () => Array.from(form.elements).filter((field) => !skipped(field));

        const forget = () => {
            try {
                window.localStorage.removeItem(key);
            } catch {
                // Nothing to do: the draft simply outlives its usefulness.
            }

            if (note) {
                note.hidden = true;
            }
        };

        const remember = () => {
            const data = {};

            fields().forEach((field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.checked) {
                        data[field.name] = field.value;
                    }

                    return;
                }

                if (field.value !== '') {
                    data[field.name] = field.value;
                }
            });

            try {
                if (Object.keys(data).length === 0) {
                    window.localStorage.removeItem(key);

                    return;
                }

                window.localStorage.setItem(key, JSON.stringify({ at: Date.now(), data }));
            } catch {
                // Storage full or blocked: carry on without a draft rather than
                // interrupting someone mid-form.
                return;
            }

            if (note) {
                note.hidden = false;
            }
        };

        const restore = () => {
            let stored;

            try {
                stored = JSON.parse(window.localStorage.getItem(key) ?? 'null');
            } catch {
                return false;
            }

            if (!stored?.data) {
                return false;
            }

            let filled = 0;

            fields().forEach((field) => {
                const value = stored.data[field.name];

                if (value === undefined) {
                    return;
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.value === value && !field.checked) {
                        field.checked = true;
                        filled += 1;
                    }

                    return;
                }

                // Anything the server already put back after a failed
                // validation wins: it is the more recent truth.
                if (field.value === '') {
                    field.value = value;
                    filled += 1;
                }
            });

            return filled > 0;
        };

        if (restore() && note) {
            note.hidden = false;
        }

        form.addEventListener('input', remember);
        form.addEventListener('change', remember);
        form.addEventListener('submit', forget);

        clearButton?.addEventListener('click', () => {
            fields().forEach((field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;

                    return;
                }

                field.value = '';
            });

            forget();
        });
    });
}
