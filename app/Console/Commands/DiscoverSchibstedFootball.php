<?php

namespace App\Console\Commands;

use App\Services\Schibsted\SchibstedFootballDiscoveryService;
use Illuminate\Console\Command;

class DiscoverSchibstedFootball extends Command
{
    protected $signature = 'football:schibsted-discover
        {--dry-run : Do not write the JSON catalog}
        {--tournament= : Filter by tournament name/slug or force a numeric season ID}
        {--output= : Write catalog to a custom path}';

    protected $description = 'Discover Schibsted/VG football tournaments and update the local tournament catalog.';

    public function handle(SchibstedFootballDiscoveryService $discovery): int
    {
        $filter = $this->option('tournament') ?: null;
        $output = $this->option('output') ?: null;

        $this->info('Discovering Schibsted/VG football API...');

        if ($filter) {
            $this->line('Filter: '.$filter);
        }

        $catalog = $discovery->discover($filter);
        $summary = $discovery->summarize($catalog);

        $this->newLine();
        $this->info('Discovery summary');
        $this->line('Tournaments: '.$summary['tournaments']);
        $this->line('Seasons: '.$summary['seasons']);

        if (!empty($summary['capabilities'])) {
            $this->line('Confirmed capabilities:');

            foreach ($summary['capabilities'] as $capability => $count) {
                $this->line(sprintf(' - %s: %d', $capability, $count));
            }
        } else {
            $this->line('Confirmed capabilities: none');
        }

        if (!empty($summary['errors'])) {
            $this->warn('Observed endpoint failures: '.count($summary['errors']));

            foreach (array_slice($summary['errors'], 0, 8) as $error) {
                $this->line(sprintf(
                    ' - %s (%s)',
                    $error['endpoint'] ?? 'unknown',
                    $error['reason'] ?? 'unknown'
                ));
            }

            if (count($summary['errors']) > 8) {
                $this->line(' - ...');
            }
        }

        $rows = [];

        foreach ($catalog['tournaments'] ?? [] as $tournament) {
            $confirmedCapabilities = array_filter($tournament['capabilities'] ?? [], function ($status) {
                return $status === true || $status === 'confirmed';
            });

            $rows[] = [
                $tournament['name'] ?? 'Ukjent',
                $tournament['country'] ?? '',
                $tournament['current_season_id'] ?? '',
                implode(', ', array_keys($confirmedCapabilities)) ?: '-',
            ];
        }

        if ($rows) {
            $this->newLine();
            $this->table(['Name', 'Country', 'Season ID', 'Capabilities'], $rows);
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Dry-run: catalog was not written.');

            return self::SUCCESS;
        }

        $path = $discovery->writeCatalog($catalog, $output);

        $this->newLine();
        $this->info('Catalog written to '.$path);

        return self::SUCCESS;
    }
}
