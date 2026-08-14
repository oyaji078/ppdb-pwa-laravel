<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\SchoolProfile;
use App\Models\SchoolProgram;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function profile(): View
    {
        return view('public.profile', [
            'sections' => SchoolProfile::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function programs(): View
    {
        return view('public.programs', [
            'programs' => SchoolProgram::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function facilities(): View
    {
        return view('public.facilities', [
            'facilities' => Facility::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }
}
