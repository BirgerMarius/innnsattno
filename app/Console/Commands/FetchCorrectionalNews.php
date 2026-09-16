<?php

namespace App\Console\Commands;

use App\Services\CorrectionalNewsService;
use Illuminate\Console\Command;

class FetchCorrectionalNews extends Command
{
    protected $signature = 'correctional-news:fetch {--source= : Hent bare én kilde (nff, ky, kdi eller sivilombudet)}';
    protected $description = 'Henter og grupperer automatiske nyheter til forsiden';

    public function handle(CorrectionalNewsService $service)
    {
        $source = $this->option('source');
        if ($source && ! array_key_exists($source, config('correctional_news.sources', []))) {
            $this->error("Ukjent kilde: {$source}");
            return 1;
        }

        $reports = $service->refresh($source);
        $this->table(['Kilde', 'Funnet', 'Lagret', 'Oppdatert', 'Duplikater', 'Avvist', 'Nye klynger', 'Oppdat. klynger', 'Feil'], array_map(
            fn ($report) => [$report['source'], $report['found'], $report['stored'], $report['updated'], $report['duplicates'], $report['rejected'], $report['clusters_created'], $report['clusters_updated'], $report['error'] ?: '-'],
            $reports
        ));

        return collect($reports)->contains(fn ($report) => $report['error'] !== null) ? 1 : 0;
    }
}
