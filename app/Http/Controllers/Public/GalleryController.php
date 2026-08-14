<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('public.gallery.index', [
            'galleries' => Gallery::query()
                ->published()
                ->with('images')
                ->withCount('images')
                ->orderBy('sort_order')
                ->paginate(9),
        ]);
    }

    public function show(Gallery $gallery): View
    {
        abort_unless($gallery->is_published, 404);

        return view('public.gallery.show', [
            'gallery' => $gallery->load('images'),
        ]);
    }
}
