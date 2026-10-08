<?php
namespace App\Http\Controllers;

use App\NewsArticle;

class NewsController extends Controller
{
    public function index()
    {
        // `fetched_at` is set only when a new article is created. It is therefore a
        // stable fallback for sources that omit a publication date; `created_at` is
        // retained for older rows without a fetched timestamp.
        $publicDate = 'COALESCE(published_at, fetched_at, created_at)';
        $cutoff = now('Europe/Oslo')->subDays(10);

        $articles = NewsArticle::with('source')
            ->where('status', NewsArticle::STATUS_PUBLISHED)
            ->whereRaw("{$publicDate} >= ?", [$cutoff])
            ->orderByRaw("{$publicDate} DESC")
            ->paginate(12);

        return view('news.index', compact('articles'));
    }
}
