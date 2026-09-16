<?php

namespace App\Services\CorrectionalNews;

class KyNewsSource extends RssNewsSource
{
    public function key(): string { return 'ky'; }
    public function name(): string { return 'KY'; }
    public function kind(): string { return 'union'; }
    public function url(): string { return config('correctional_news.sources.ky.url'); }
}
