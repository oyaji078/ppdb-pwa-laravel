/**
 * Uploads every document an applicant picked from a single button press.
 *
 * The files go one request at a time rather than in one combined form. A
 * serverless host rejects a request body over 4.5 MB before PHP ever runs, so
 * four or five scans submitted together would fail as an unexplained error
 * while none of them does alone. Sending them separately also means one
 * rejected file does not throw away the ones that already went through.
 *
 * This is an enhancement: without JavaScript the page keeps its per-document
 * forms and their own upload buttons, which still work one at a time.
 */

const page = document.querySelector('[data-documents]');

if (page) {
    const forms = Array.from(page.querySelectorAll('form[data-document-form]'));
    const continueLink = page.querySelector('[data-documents-continue]');
    const summary = page.querySelector('[data-documents-summary]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const rowOf = (form) => form.closest('[data-document-row]');
    const inputOf = (form) => form.querySelector('input[type="file"]');

    const setStatus = (form, text, tone) => {
        const status = rowOf(form)?.querySelector('[data-document-status]');

        if (!status) {
            return;
        }

        status.textContent = text;
        status.hidden = text === '';
        status.className = {
            pending: 'mt-2 text-xs text-slate-500',
            busy: 'mt-2 text-xs text-brand-700',
            done: 'mt-2 text-xs font-medium text-emerald-700',
            error: 'mt-2 text-xs font-medium text-rose-600',
        }[tone] ?? 'mt-2 text-xs text-slate-500';
    };

    const chosen = () => forms.filter((form) => inputOf(form)?.files.length > 0);

    const announce = (text, tone) => {
        if (!summary) {
            return;
        }

        summary.textContent = text;
        summary.hidden = text === '';
        summary.className = tone === 'error'
            ? 'mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700 ring-1 ring-rose-200'
            : 'mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700 ring-1 ring-slate-200';
    };

    // The per-document buttons stay in the markup for the no-JavaScript case and
    // are only taken out of the flow once this file has run.
    forms.forEach((form) => {
        form.querySelector('[data-document-submit]')?.remove();

        inputOf(form)?.addEventListener('change', (event) => {
            const file = event.target.files[0];
            setStatus(form, file ? `Siap diunggah: ${file.name}` : '', 'pending');
            updateLabel();
        });
    });

    const updateLabel = () => {
        if (!continueLink) {
            return;
        }

        const count = chosen().length;
        const label = continueLink.querySelector('[data-documents-label]');

        if (label) {
            label.textContent = count > 0
                ? `Unggah ${count} berkas & lanjutkan`
                : 'Lanjut ke Review';
        }
    };

    const upload = async (form) => {
        setStatus(form, 'Mengunggah...', 'busy');

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            // A proxy or an upload larger than the host accepts answers with
            // something that is not JSON; the status code still tells us enough.
        }

        if (response.ok) {
            setStatus(form, `Terunggah: ${payload.document?.name ?? inputOf(form).files[0].name}`, 'done');

            return true;
        }

        const reason = payload.errors?.file?.[0]
            ?? payload.message
            ?? (response.status === 413
                ? 'Berkas terlalu besar untuk diunggah.'
                : `Gagal diunggah (${response.status}).`);

        setStatus(form, reason, 'error');

        return false;
    };

    continueLink?.addEventListener('click', async (event) => {
        const pending = chosen();

        if (pending.length === 0) {
            return; // Nothing picked: let the link go to the next step.
        }

        event.preventDefault();

        if (continueLink.dataset.busy === '1') {
            return;
        }

        continueLink.dataset.busy = '1';
        continueLink.setAttribute('aria-disabled', 'true');
        continueLink.classList.add('pointer-events-none', 'opacity-60');

        let uploaded = 0;

        for (const form of pending) {
            announce(`Mengunggah berkas ${uploaded + 1} dari ${pending.length}...`);

            let ok = false;

            try {
                ok = await upload(form);
            } catch {
                setStatus(form, 'Koneksi terputus saat mengunggah.', 'error');
            }

            if (!ok) {
                announce(
                    'Sebagian berkas gagal diunggah. Berkas yang berhasil sudah tersimpan; perbaiki yang bertanda merah lalu tekan tombol ini lagi.',
                    'error',
                );

                continueLink.dataset.busy = '';
                continueLink.removeAttribute('aria-disabled');
                continueLink.classList.remove('pointer-events-none', 'opacity-60');

                return;
            }

            uploaded += 1;
            inputOf(form).value = '';
        }

        announce(`${uploaded} berkas terunggah. Membuka halaman review...`);
        window.location.href = continueLink.href;
    });

    updateLabel();
}
