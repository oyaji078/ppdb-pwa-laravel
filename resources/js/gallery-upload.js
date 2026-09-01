/**
 * Sends album photos one request at a time.
 *
 * The host rejects a request body over 4.5 MB before PHP runs, and the form
 * allows twenty photos of up to 3 MB, so posting a batch together fails as an
 * unexplained error the moment someone picks a second large photo. One request
 * per photo stays well inside the limit and lets the page report progress.
 *
 * Two forms use it. On "manage album" the album already exists and the photos
 * go straight to it. On "new album" there is nothing to attach them to yet, so
 * the album is created first and the photos follow.
 *
 * Without JavaScript both forms still submit normally: creating an album saves
 * whatever photos fit in that one request, and more can be added afterwards.
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const headers = () => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf(),
});

const report = (form, text, tone = 'busy') => {
    const status = form.querySelector('[data-gallery-status]');

    if (!status) {
        return;
    }

    status.textContent = text;
    status.hidden = text === '';
    status.className = tone === 'error'
        ? 'mt-2 text-xs font-medium text-rose-600'
        : 'mt-2 text-xs text-brand-700';
};

/** Reads whatever the server said went wrong, however it phrased it. */
const reasonFrom = (payload, response) =>
    payload?.errors?.['images.0']?.[0]
    ?? payload?.errors?.images?.[0]
    ?? payload?.message
    ?? (response.status === 413
        ? 'Foto terlalu besar untuk diunggah.'
        : `Gagal mengunggah (${response.status}).`);

/**
 * Uploads the chosen files to an album, one at a time.
 *
 * @returns {Promise<boolean>} whether every file went through
 */
const uploadEach = async (form, url, files, caption) => {
    for (let index = 0; index < files.length; index++) {
        report(form, `Mengunggah foto ${index + 1} dari ${files.length}...`);

        const body = new FormData();
        body.append('images[]', files[index]);

        if (caption) {
            body.append('caption', caption);
        }

        let response;
        let payload = {};

        try {
            response = await fetch(url, { method: 'POST', body, credentials: 'same-origin', headers: headers() });
        } catch {
            report(form, 'Koneksi terputus saat mengunggah.', 'error');

            return false;
        }

        try {
            payload = await response.json();
        } catch {
            // A rejected upload can answer with something that is not JSON; the
            // status code still says enough.
        }

        if (!response.ok) {
            report(form, `Foto "${files[index].name}": ${reasonFrom(payload, response)}`, 'error');

            return false;
        }
    }

    return true;
};

// -- Manage album: the album exists, so the photos go straight to it ----------

const uploadForm = document.querySelector('form[data-gallery-upload]');

if (uploadForm) {
    uploadForm.addEventListener('submit', async (event) => {
        const input = uploadForm.querySelector('[data-gallery-input]');
        const files = Array.from(input?.files ?? []);

        if (files.length === 0) {
            return; // Let the browser's own required check speak.
        }

        event.preventDefault();

        const caption = uploadForm.querySelector('[name="caption"]')?.value ?? '';

        if (await uploadEach(uploadForm, uploadForm.action, files, caption)) {
            report(uploadForm, `${files.length} foto terunggah. Memuat ulang...`);
            window.location.reload();
        }
    });
}

// -- New album: create it first, then attach the photos ----------------------

const createForm = document.querySelector('form[data-gallery-create]');

if (createForm) {
    createForm.addEventListener('submit', async (event) => {
        const input = createForm.querySelector('[data-gallery-input]');
        const files = Array.from(input?.files ?? []);

        if (files.length === 0) {
            return; // Nothing to stage: an ordinary form post creates the album.
        }

        event.preventDefault();

        const button = createForm.querySelector('button[type="submit"]');
        const label = createForm.querySelector('[data-gallery-submit-label]');

        button?.setAttribute('disabled', 'disabled');

        if (label) {
            label.textContent = 'Menyimpan...';
        }

        const restore = () => {
            button?.removeAttribute('disabled');

            if (label) {
                label.textContent = 'Simpan';
            }
        };

        report(createForm, 'Membuat album...');

        // The album has to exist before a photo can belong to it, so the fields
        // go first, without the files.
        const fields = new FormData(createForm);
        fields.delete('images[]');

        let created;

        try {
            const response = await fetch(createForm.action, {
                method: 'POST',
                body: fields,
                credentials: 'same-origin',
                headers: headers(),
            });

            created = await response.json();

            if (!response.ok) {
                report(createForm, created?.errors?.title?.[0] ?? created?.message ?? 'Album gagal dibuat.', 'error');
                restore();

                return;
            }
        } catch {
            report(createForm, 'Album gagal dibuat. Periksa koneksi Anda.', 'error');
            restore();

            return;
        }

        const caption = createForm.querySelector('[name="caption"]')?.value ?? '';
        const target = createForm.dataset.imagesUrl.replace('__id__', created.id);

        const ok = await uploadEach(createForm, target, files, caption);

        // The album itself was saved either way, so the operator is taken to it
        // rather than losing the work over a failed photo.
        report(createForm, ok ? 'Selesai. Membuka album...' : 'Album dibuat, tetapi ada foto yang gagal. Membuka album...', ok ? 'busy' : 'error');

        window.setTimeout(() => {
            window.location.href = created.editUrl;
        }, ok ? 0 : 2500);
    });
}
