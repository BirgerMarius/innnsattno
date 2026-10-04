<?php

namespace App\Console\Commands;

use App\Services\FangenyttImporter;
use Illuminate\Console\Command;

class ImportFangenytt extends Command
{
    protected $signature = 'fangenytt:import
        {number : Fangenytt-nummer}
        {url : Original HTTPS-URL hos Fangeforeningen}
        {--edition= : Valgfri utgavebetegnelse}
        {--published-at= : Valgfri publiseringsdato}
        {--source=manual : Kildetype, for eksempel manual, email eller website}';
    protected $description = 'Validerer, importerer og publiserer én ny Fangenytt-utgave';

    public function handle(FangenyttImporter $importer): int
    {
        $result = $importer->import([
            'number' => $this->argument('number'),
            'original_url' => $this->argument('url'),
            'edition' => $this->option('edition'),
            'published_at' => $this->option('published-at'),
            'source' => $this->option('source'),
        ]);

        $result['success'] ? $this->info($result['message']) : $this->error($result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
