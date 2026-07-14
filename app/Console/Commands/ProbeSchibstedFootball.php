<?php

namespace App\Console\Commands;

use App\Services\Schibsted\JsonStructureInspector;
use App\Services\Schibsted\SchibstedSportsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ProbeSchibstedFootball extends Command
{
    protected $signature = 'football:schibsted-probe
        {--season=7767 : Sesong-ID}
        {--tournament=19 : Turnerings-ID}
        {--event= : Kamp-/event-ID}
        {--participant= : Deltaker-/lag-ID}
        {--profile=season : Profil: season, event, participant eller all-safe}
        {--save : Lagre probe-rapport}
        {--delay=400 : Forsinkelse mellom kall i millisekunder}
        {--timeout= : Timeout i sekunder}';

    protected $description = 'Test et lite, eksplisitt sett Schibsted SportsNext-kandidatstier.';

    private const MAX_CALLS = 30;

    public function handle(SchibstedSportsClient $client, JsonStructureInspector $inspector): int
    {
        try {
            $paths = $this->candidatePaths();
        } catch (InvalidArgumentException $exception) {
            $this->error('Ugyldige argumenter: '.$exception->getMessage());

            return SchibstedSportsClient::EXIT_INVALID_ARGUMENTS;
        }

        $this->info('Schibsted SportsNext kontrollert probe');
        $this->line('Profil: '.$this->option('profile'));
        $this->line('Planlagte kall: '.count($paths));

        if (count($paths) > self::MAX_CALLS) {
            $this->error('For mange kandidatstier. Maks '.self::MAX_CALLS.' per kjøring.');

            return SchibstedSportsClient::EXIT_INVALID_ARGUMENTS;
        }

        $rows = [];
        $results = [];
        $delay = max(250, (int) $this->option('delay'));
        $exitCode = self::SUCCESS;

        foreach ($paths as $index => $path) {
            if ($index > 0) {
                usleep($delay * 1000);
            }

            $result = $client->request($path, [
                'method' => 'GET',
                'timeout' => $this->option('timeout') ? (int) $this->option('timeout') : null,
                'cache' => false,
            ]);

            $summary = $result['json_valid'] ? $inspector->inspect($result['json']) : null;
            $classification = $this->classification($result);

            if ($classification === 'nettverksfeil') {
                $exitCode = SchibstedSportsClient::EXIT_NETWORK_ERROR;
            }

            $probeResult = [
                'path' => $result['path'],
                'status' => $result['status'],
                'classification' => $classification,
                'content_type' => $result['content_type'],
                'valid_json' => $result['json_valid'],
                'response_size' => $result['response_size'],
                'top_level_keys' => $summary['top_level_keys'] ?? [],
                'error' => $result['error'],
                'checked_at' => now()->toIso8601String(),
            ];

            $results[] = $probeResult;
            $rows[] = [
                $classification,
                $result['status'] ?? '-',
                $result['path'],
                $result['json_valid'] ? 'ja' : 'nei',
                $result['response_size'],
                implode(', ', array_slice($probeResult['top_level_keys'], 0, 6)) ?: '-',
            ];
        }

        $this->table(['Status', 'HTTP', 'Sti', 'JSON', 'Bytes', 'Toppnivåfelter'], $rows);

        if ($this->option('save')) {
            $path = storage_path('app/reference/schibsted/probes/probe_'.now()->format('Ymd_His').'.json');
            $directory = dirname($path);

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($path, json_encode([
                'profile' => $this->option('profile'),
                'results' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
            $this->info('Lagret probe-rapport: '.$path);
        }

        return $exitCode;
    }

    private function candidatePaths(): array
    {
        $profile = (string) $this->option('profile');
        $season = $this->requiredNumericOption('season', ['season', 'all-safe']);
        $tournament = $this->numericOption('tournament');
        $event = $this->requiredNumericOption('event', ['event', 'all-safe']);
        $participant = $this->requiredNumericOption('participant', ['participant', 'all-safe']);

        $seasonPaths = [
            '/tournaments/seasons/{seasonId}/schedule',
            '/tournaments/seasons/{seasonId}/standings',
            '/tournaments/seasons/{seasonId}',
            '/tournaments/{tournamentId}',
            '/tournaments/{tournamentId}/seasons',
            '/tournaments/seasons/{seasonId}/participants',
            '/tournaments/seasons/{seasonId}/teams',
            '/tournaments/seasons/{seasonId}/statistics',
            '/tournaments/seasons/{seasonId}/lineups',
        ];

        $eventPaths = [
            '/events/{eventId}',
            '/events/{eventId}/details',
            '/events/{eventId}/lineups',
            '/events/{eventId}/statistics',
            '/events/{eventId}/incidents',
            '/matches/{eventId}',
            '/matches/{eventId}/statistics',
        ];

        $participantPaths = [
            '/participants/{participantId}',
            '/participants/{participantId}/matches',
            '/participants/{participantId}/statistics',
            '/teams/{participantId}',
            '/teams/{participantId}/players',
            '/teams/{participantId}/fixtures',
        ];

        if ($profile === 'season') {
            $paths = $seasonPaths;
        } elseif ($profile === 'event') {
            $paths = $eventPaths;
        } elseif ($profile === 'participant') {
            $paths = $participantPaths;
        } elseif ($profile === 'all-safe') {
            $paths = array_merge($seasonPaths, $eventPaths, $participantPaths);
        } else {
            throw new InvalidArgumentException('Ukjent profil. Bruk season, event, participant eller all-safe.');
        }

        return array_values(array_unique(array_map(function (string $path) use ($season, $tournament, $event, $participant) {
            return str_replace(
                ['{seasonId}', '{tournamentId}', '{eventId}', '{participantId}'],
                [(string) $season, (string) $tournament, (string) $event, (string) $participant],
                $path
            );
        }, $paths)));
    }

    private function numericOption(string $name): ?int
    {
        $value = $this->option($name);

        if (!$value) {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            throw new InvalidArgumentException('--'.$name.' må være numerisk.');
        }

        return (int) $value;
    }

    private function requiredNumericOption(string $name, array $profiles): ?int
    {
        $profile = (string) $this->option('profile');
        $value = $this->option($name);

        if (!in_array($profile, $profiles, true)) {
            return $value && ctype_digit((string) $value) ? (int) $value : null;
        }

        if (!$value || !ctype_digit((string) $value)) {
            throw new InvalidArgumentException('--'.$name.' må settes for profil '.$profile.'.');
        }

        return (int) $value;
    }

    private function classification(array $result): string
    {
        if ($result['network_error']) {
            return 'nettverksfeil';
        }

        if ($result['is_redirect']) {
            return 'redirect';
        }

        $status = (int) $result['status'];

        if ($status >= 200 && $status < 300) {
            return 'bekreftet';
        }

        if (in_array($status, [401, 403, 404], true)) {
            return 'avvist';
        }

        return 'ukjent';
    }
}
