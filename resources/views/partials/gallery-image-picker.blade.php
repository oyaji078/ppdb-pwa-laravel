{{--
    The photo picker, shared by the "new album" and "manage album" screens so
    the two cannot drift apart.

    @param bool $creating  true on the new-album form, where the album does not
                           exist yet and the photos are sent after it is saved
--}}
<div class="sm:col-span-2">
    <label for="images" class="form-label">Pilih Foto (bisa lebih dari satu)</label>
    <input type="file" name="images[]" id="images" multiple accept=".jpg,.jpeg,.png,.webp"
           @unless ($creating) required @endunless
           data-gallery-input
           class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 ring-1 ring-slate-300 ring-inset file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">

    <p class="mt-1 text-xs text-slate-500">
        JPG, PNG, atau WEBP. Maksimal 3 MB per foto, hingga 20 foto sekaligus.
        Foto dikirim satu per satu, jadi banyaknya pilihan tidak menjadi masalah.
    </p>

    {{-- Per-file failures land on "images.0", "images.1", ... so a plain
         @error('images') would hide them. The wildcard lookup returns one array
         per key, hence the spread; identical messages across files are
         collapsed into one line. --}}
    @php
        $imageErrors = array_unique(array_merge(
            $errors->get('images'),
            ...array_values($errors->get('images.*'))
        ));
    @endphp
    @foreach ($imageErrors as $message)
        <p class="form-error">
            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $message }}</span>
        </p>
    @endforeach

    <p data-gallery-status hidden aria-live="polite" class="mt-2 text-xs text-slate-500"></p>
</div>

<div>
    <x-form.input name="caption" label="Keterangan (opsional)" placeholder="Berlaku untuk semua foto" />
</div>
