{{--
    Shown once a step has an unsent draft kept in this browser. Hidden until the
    script has something to report, and never rendered server-side, because the
    draft never reaches the server.

    The clear button matters: a registration form holds personal details, and
    someone filling it in at a shared computer needs a way to wipe it that does
    not depend on knowing what browser storage is.
--}}
<div data-draft-note hidden
     class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
    <span class="flex items-center gap-2">
        <x-icon name="save" class="h-4 w-4 shrink-0" />
        Isian ini tersimpan otomatis di perangkat Anda, jadi tidak hilang bila halaman tertutup.
    </span>

    <button type="button" data-draft-clear
            class="shrink-0 rounded-md px-2 py-1 font-medium text-amber-900 underline underline-offset-2 hover:bg-amber-100">
        Kosongkan
    </button>
</div>
