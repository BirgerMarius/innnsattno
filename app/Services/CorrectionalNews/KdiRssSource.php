<?php

namespace App\Services\CorrectionalNews;

class KdiRssSource extends RssNewsSource
{
    public function key(): string { return 'kdi'; }
    public function name(): string { return 'Kriminalomsorgsdirektoratet'; }
    public function kind(): string { return 'national'; }
    public function url(): string { return config('correctional_news.sources.kdi.url'); }
}
