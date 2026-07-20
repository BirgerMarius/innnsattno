<?php

namespace App\Console\Commands;

use App\NewsSource;
use App\Services\News\NewsFeedService;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature='news:fetch {--source= : Hent bare kilden med denne sluggen}';
    protected $description='Henter nyhetsartikler fra aktive, registrerte kilder';
    public function handle(NewsFeedService $service)
    {
        $slug=$this->option('source');
        if($slug && !NewsSource::where('slug',$slug)->exists()) { $this->error("Ukjent nyhetskilde: {$slug}"); return 1; }
        if($slug && !NewsSource::where('slug',$slug)->where('is_active',true)->exists()) { $this->error("Nyhetskilden {$slug} er deaktivert."); return 1; }
        $reports=$service->fetchActive($slug);
        $this->table(['Kilde','Funnet','Nye','Dubletter','Feil'],array_map(function($r){return [$r['source'],$r['found'],$r['new'],$r['duplicates'],$r['error']?:'-'];},$reports));
        return collect($reports)->contains(function($r){return $r['error']!==null;}) ? 1 : 0;
    }
}
