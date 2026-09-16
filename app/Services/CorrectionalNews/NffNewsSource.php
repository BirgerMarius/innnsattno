<?php

namespace App\Services\CorrectionalNews;

class NffNewsSource extends RssNewsSource
{
    public function key(): string { return 'nff'; }
    public function name(): string { return 'NFF'; }
    public function kind(): string { return 'union'; }
    public function url(): string { return config('correctional_news.sources.nff.url'); }
}
