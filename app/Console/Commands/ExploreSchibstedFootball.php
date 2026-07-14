<?php

namespace App\Console\Commands;

use App\Services\Schibsted\JsonStructureInspector;
use App\Services\Schibsted\SchibstedSportsClient;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ExploreSchibstedFootball extends Command
{
    protected $signature = 'football:schibsted-explore
        {--path= : Relativ API-sti som skal hentes}
        {--season= : Sesong-ID for kjente sesongstier}
        {--tournament= : Turnerings-ID for metadata i rapporten}
        {--event= : Kamp-/event-ID for metadata i rapporten}
        {--participant= : Deltaker-/lag-ID for metadata i rapporten}
        {--method=GET : HTTP-metode, kun GET eller HEAD}
        {--output= : Trygt filnavn under storage/app/reference/schibsted/responses}
        {--save : Lagre rårespons og metadata}
        {--summary : Vis kort strukturoversikt}
        {--show-body : Skriv responsbody, begrenset av --max-body}
        {--max-body=4000 : Maks antall tegn body som skrives}
        {--timeout= : Timeout i sekunder}
        {--no-cache : Ikke bruk klientcache}';

    protected $description = 'Utforsk Schibsted/VG SportsNext API med kontrollerte, lesende kall.';

    public function handle(SchibstedSportsClient $client, JsonStructureInspector $inspector): int
    {
        try {
            $path = $this->resolvePath();
            $result = $client->request($path, [
                'method' => (string) $this->option('method'),
                'timeout' => $this->option('timeout') ? (int) $this->option('timeout') : null,
                'cache' => !$this->option('no-cache'),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error('Ugyldige argumenter: '.$exception->getMessage());

            return SchibstedSportsClient::EXIT_INVALID_ARGUMENTS;
        }

        $this->printMetadata($result);

        if ($result['network_error']) {
            $this->error('Nettverksfeil: '.$result['error']);

            return SchibstedSportsClient::EXIT_NETWORK_ERROR;
        }

        if ($result['json_valid'] && is_array($result['json'])) {
            $summary = $inspector->inspect($result['json']);
            $this->printJsonSummary($summary);
        } elseif ($result['body'] !== '') {
            $this->warn('Responsen er ikke gyldig JSON.');
            $this->line('JSON-feil: '.($result['json_error'] ?: 'ukjent'));
        }

        if ($this->option('show-body')) {
            $this->printBody($result['body'], (int) $this->option('max-body'));
        }

        if ($this->option('save')) {
            try {
                $savedPath = $client->saveResponse($result, $this->option('output') ?: null);
                $this->info('Lagret respons: '.$savedPath);
            } catch (InvalidArgumentException $exception) {
                $this->error('Kunne ikke lagre respons: '.$exception->getMessage());

                return SchibstedSportsClient::EXIT_INVALID_ARGUMENTS;
            }
        }

        if (!$result['is_successful']) {
            $this->warn('API-kallet ga ikke 2xx-status.');

            return SchibstedSportsClient::EXIT_HTTP_ERROR;
        }

        return self::SUCCESS;
    }

    private function resolvePath(): string
    {
        $path = $this->option('path');

        if ($path) {
            return (string) $path;
        }

        $season = $this->option('season');

        if ($season && ctype_digit((string) $season)) {
            return '/tournaments/seasons/'.$season.'/schedule';
        }

        throw new InvalidArgumentException('Bruk --path= eller --season=.');
    }

    private function printMetadata(array $result): void
    {
        $this->info('Schibsted SportsNext API Explorer');
        $this->line('Relativ sti: '.$result['path']);
        $this->line('Full URL: '.$result['url']);
        $this->line('Metode: '.$result['method']);
        $this->line('HTTP-status: '.($result['status'] ?? 'ingen'));
        $this->line('Content-Type: '.($result['content_type'] ?: 'ukjent'));
        $this->line('Responstid: '.$result['duration_ms'].' ms');
        $this->line('Responsstørrelse: '.$result['response_size'].' bytes');
        $this->line('Gyldig JSON: '.($result['json_valid'] ? 'ja' : 'nei'));

        if ($result['is_redirect']) {
            $this->warn('Redirect observert. Dette regnes ikke som bekreftet API-støtte.');
        }

        if ($result['error']) {
            $this->line('API-feil: '.$result['error']);
        }
    }

    private function printJsonSummary(array $summary): void
    {
        $this->newLine();
        $this->info('Strukturoversikt');
        $this->line('Toppnivåtype: '.$summary['top_level_type']);
        $this->line('Toppnivåfelter: '.($summary['top_level_keys'] ? implode(', ', $summary['top_level_keys']) : '-'));

        if ($summary['list_counts']) {
            $this->line('Listeantall:');
            foreach ($summary['list_counts'] as $path => $listSummary) {
                $this->line(sprintf(
                    ' - %s: %d forekomster, viser første %d',
                    $path,
                    $listSummary['total'],
                    $listSummary['shown']
                ));

                foreach ($listSummary['examples'] as $example) {
                    $this->line(sprintf('   - %s: %d', $example['path'], $example['count']));
                }

                if ($listSummary['hidden'] > 0) {
                    $this->line(sprintf('   - ... %d flere', $listSummary['hidden']));
                }
            }
        }

        if ($summary['first_item_fields']) {
            $this->line('Felt i første listeelement:');
            foreach ($summary['first_item_fields'] as $path => $fields) {
                $this->line(sprintf(' - %s: %s', $path, implode(', ', $fields)));
            }
        }

        $signals = array_keys(array_filter($summary['signals']));
        $this->line('Observerte nøkkelsignaler: '.($signals ? implode(', ', $signals) : '-'));

        $idFields = $summary['id_fields'];
        $this->line(sprintf(
            'ID-felt: %d forekomster, viser første %d',
            $idFields['total'],
            $idFields['shown']
        ));
        $this->line($idFields['examples'] ? ' - '.implode(', ', $idFields['examples']) : ' -');

        if ($idFields['hidden'] > 0) {
            $this->line(sprintf(' - ... %d flere', $idFields['hidden']));
        }
    }

    private function printBody(string $body, int $maxBody): void
    {
        $maxBody = max(0, $maxBody);
        $visible = mb_substr($body, 0, $maxBody);

        $this->newLine();
        $this->info('Responsbody');
        $this->line($visible);

        if (mb_strlen($body) > $maxBody) {
            $this->warn('Body er avkortet til '.$maxBody.' tegn.');
        }
    }
}
