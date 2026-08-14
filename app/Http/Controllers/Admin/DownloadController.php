<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    public function index(): View
    {
        return view('admin.downloads.index', [
            'downloads' => Download::query()->orderBy('sort_order')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.downloads.form', ['download' => new Download(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request, fileRequired: true);
        $file = $request->file('file');

        Download::query()->create(array_merge($validated, $this->fileAttributes($file)));

        return redirect()->route('admin.downloads.index')->with('success', 'Berkas unduhan berhasil ditambahkan.');
    }

    public function edit(Download $download): View
    {
        return view('admin.downloads.form', ['download' => $download]);
    }

    public function update(Request $request, Download $download): RedirectResponse
    {
        $data = $this->validated($request, fileRequired: false);

        if ($request->hasFile('file')) {
            $previous = $download->file_path;
            $data = array_merge($data, $this->fileAttributes($request->file('file')));
            $this->deleteFile($previous);
        }

        $download->update($data);

        return redirect()->route('admin.downloads.index')->with('success', 'Berkas unduhan berhasil diperbarui.');
    }

    public function destroy(Download $download): RedirectResponse
    {
        $this->deleteFile($download->file_path);

        $title = $download->title;
        $download->delete();

        return redirect()->route('admin.downloads.index')
            ->with('success', sprintf('Berkas "%s" dihapus.', $title));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $fileRequired): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'file.required' => 'Berkas wajib diunggah.',
            'file.mimes' => 'Format berkas harus PDF, DOC, DOCX, XLS, XLSX, atau ZIP.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        unset($validated['file']);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function fileAttributes(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::random(40).'.'.$extension;

        return [
            'file_path' => $file->storeAs('cms/downloads', $name, ['disk' => 'public']),
            'original_name' => substr($file->getClientOriginalName(), 0, 255),
            'extension' => $extension,
            'file_size' => $file->getSize() ?: 0,
        ];
    }

    private function deleteFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus berkas unduhan.', ['path' => $path, 'message' => $e->getMessage()]);
        }
    }
}
