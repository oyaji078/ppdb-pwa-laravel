<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $news = News::query()
            ->published()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")
            ))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('public.news.index', [
            'news' => $news,
            'search' => $search,
        ]);
    }

    public function show(News $news): View
    {
        abort_unless($news->is_published && ($news->published_at === null || $news->published_at->isPast()), 404);

        $news->increment('views');

        return view('public.news.show', [
            'article' => $news,
            'related' => News::query()
                ->published()
                ->whereKeyNot($news->id)
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
