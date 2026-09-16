<?php

namespace App\Services\CorrectionalNews;

class SivilombudetRssSource extends RssNewsSource
{
    public function key(): string { return 'sivilombudet'; }
    public function name(): string { return 'Sivilombudet'; }
    public function kind(): string { return 'national'; }
    public function url(): string { return config('correctional_news.sources.sivilombudet.url'); }
}
