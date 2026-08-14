<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(): View
    {
        return view('public.downloads', [
            'downloads' => Download::query()->published()->orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    /**
     * Public documents live on the public disk but are still streamed through
     * the app so the download counter stays accurate.
     */
    public function download(Download $download): StreamedResponse
    {
        abort_unless($download->is_published, 404);
        abort_unless(Storage::disk('public')->exists($download->file_path), 404);

        $download->increment('download_count');

        return Storage::disk('public')->download($download->file_path, $download->original_name);
    }
}
