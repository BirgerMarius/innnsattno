<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Deliberately conservative season selection; failures never change a selection. */
class SportsNextSeasonControlService
{
    public function __construct(private SportsNextSeasonSelectionService $selections) {}

    public function checkAll(): array
    {
        $lock = Cache::lock('sportsnext.season-control', 600);
        if (! $lock->get()) return [];
        try { return array_map(fn ($key) => $this->check($key), array_keys(config('services.schibsted_sports.competitions', []))); }
        finally { $lock->release(); }
    }

    public function check(string $key): array
    {
        $definition = config('services.schibsted_sports.competitions.'.$key);
        if (! is_array($definition)) return ['ok' => false, 'reason' => 'Ukjent turnering.'];
        $default = (int) config('services.schibsted_sports.'.$definition['season_config']);
        $effective = $this->selections->effectiveSeasonId($key, $default);
        try {
            $catalog = $this->get('/tournaments/'.$definition['tournament_id'].'/seasons');
            $seasons = $catalog['tournamentSeasons'] ?? [];
            $currentId = $catalog['tournament']['currentSeasonId'] ?? null;
            if (! is_array($seasons) || ! is_numeric($currentId)) throw new \UnexpectedValueException('Sesongoversikten mangler currentSeasonId eller sesonger.');
            $old = $this->season($seasons, $effective);
            $candidate = $this->season($seasons, (int) $currentId);
            if (! $old || ! $candidate) throw new \UnexpectedValueException('Gjeldende eller foreslått sesong mangler i sesongoversikten.');
            $base = ['api_current_season_id' => (int) $currentId, 'api_current_season_name' => $candidate['name'] ?? null, 'effective_start_date' => $old['startDate'] ?? null, 'effective_end_date' => $old['endDate'] ?? null, 'last_checked_at' => now(), 'last_success_at' => now()];
            if ((int) $currentId === $effective) {
                $this->selections->upsert($key, $base + ['status' => 'Aktuell', 'status_reason' => 'API-et peker på valgt sesong.', 'candidate_season_id' => null, 'candidate_observations' => 0]);
                return ['ok' => true, 'status' => 'Aktuell'];
            }
            if (! $this->isNewer($seasons, $effective, (int) $currentId, $old, $candidate)) {
                $this->selections->upsert($key, $base + ['status' => 'Manuell kontroll', 'status_reason' => 'API-ets avvik er ikke en entydig nyere sesong.']);
                return ['ok' => true, 'status' => 'Manuell kontroll'];
            }
            $row = $this->selections->row($key, $default);
            if ((int) ($row?->blocked_candidate_season_id ?? 0) === (int) $currentId) {
                $this->selections->upsert($key, $base + ['status' => 'Manuell kontroll', 'status_reason' => 'Kandidaten er blokkert etter tilbakeføring.']);
                return ['ok' => true, 'status' => 'Blokkert'];
            }
            $observations = ((int) ($row?->candidate_season_id ?? 0) === (int) $currentId && $row?->candidate_last_observed_at && Carbon::parse($row->candidate_last_observed_at)->lte(now()->subHours((int) config('services.schibsted_sports.season_switch_observation_hours', 12)))) ? (int) $row->candidate_observations + 1 : 1;
            $candidateValues = $base + ['candidate_season_id' => (int) $currentId, 'candidate_name' => $candidate['name'] ?? null, 'candidate_start_date' => $candidate['startDate'] ?? null, 'candidate_end_date' => $candidate['endDate'] ?? null, 'candidate_observations' => $observations, 'candidate_first_observed_at' => $observations === 1 ? now() : $row->candidate_first_observed_at, 'candidate_last_observed_at' => now()];
            $reason = $this->readiness($key, $definition, $old, $candidate, $effective, $observations);
            if ($reason !== null) { $this->selections->upsert($key, $candidateValues + ['status' => 'Avventer', 'status_reason' => $reason]); return ['ok' => true, 'status' => 'Avventer']; }
            if (! ($definition['automatic_supported'] ?? false) || ! ($row?->automatic_enabled ?? false)) {
                $this->selections->upsert($key, $candidateValues + ['status' => 'Klar for manuell godkjenning', 'status_reason' => 'Alle datakontroller er bestått; automatikk er ikke aktiv.']);
                return ['ok' => true, 'status' => 'Klar for manuell godkjenning'];
            }
            $this->selections->select($key, (int) $currentId, 'automatic', 'To adskilte kontroller, karenstid og gamle/nye kampdata er validert.', false, $effective);
            return ['ok' => true, 'status' => 'Byttet'];
        } catch (Throwable $e) {
            $this->selections->upsert($key, ['last_checked_at' => now(), 'status' => 'Kontrollfeil', 'status_reason' => 'Kunne ikke kontrollere sesongdata; valgt sesong er uendret.']);
            return ['ok' => false, 'reason' => $e instanceof RequestException ? 'HTTP-feil' : 'API-feil'];
        }
    }

    private function readiness(string $key, array $definition, array $old, array $candidate, int $oldId, int $observations): ?string
    {
        if ($observations < 2) return 'Venter på samme kandidat i en ny vellykket kontroll.';
        if (empty($old['endDate']) || Carbon::parse($old['endDate'], 'Europe/Oslo')->endOfDay()->addDays((int) config('services.schibsted_sports.season_switch_grace_days', 14))->isFuture()) return 'Gammel sesongs sluttdato eller karenstid er ikke passert.';
        $oldSchedule = $this->get('/tournaments/seasons/'.$oldId.'/schedule');
        if (! $this->complete($oldSchedule)) return 'Gammelt kampoppsett har gjenstående eller uavklarte kamper.';
        $newSchedule = $this->get('/tournaments/seasons/'.$candidate['id'].'/schedule');
        $newStandings = $this->get('/tournaments/seasons/'.$candidate['id'].'/standings');
        if (! is_array($newSchedule['events'] ?? null) || count($newSchedule['events']) === 0 || ! is_array($newSchedule['participants'] ?? null) || ! is_array($newStandings['standings'] ?? null)) return 'Ny sesong mangler validerte, relevante kamp- eller tabelldata.';
        return null;
    }

    private function complete(array $schedule): bool
    {
        if (! is_array($schedule['events'] ?? null) || $schedule['events'] === []) return false;
        foreach ($schedule['events'] as $event) {
            if (! is_array($event)) return false;
            $type = strtolower((string) ($event['status']['type'] ?? ''));
            $subtype = strtolower((string) ($event['status']['subtype'] ?? ''));
            if (! in_array($type, ['finished', 'done', 'ended', 'cancelled', 'canceled'], true) && ! in_array($subtype, ['finished', 'fulltime', 'full_time', 'ft', 'cancelled', 'canceled'], true)) return false;
        }
        return true;
    }

    private function isNewer(array $all, int $oldId, int $newId, array $old, array $new): bool
    {
        $oldIndex = array_search($oldId, array_column($all, 'id'), true); $newIndex = array_search($newId, array_column($all, 'id'), true);
        return $oldIndex !== false && $newIndex !== false && $newIndex > $oldIndex && ! empty($old['endDate']) && ! empty($new['startDate']) && Carbon::parse($new['startDate'])->gt(Carbon::parse($old['endDate']));
    }
    private function season(array $seasons, int $id): ?array { foreach ($seasons as $season) if ((int) ($season['id'] ?? 0) === $id) return $season; return null; }
    private function get(string $path): array { $response = Http::acceptJson()->connectTimeout(3)->timeout((int) config('services.schibsted_sports.timeout', 10))->get(rtrim(config('services.schibsted_sports.base_url'), '/').$path)->throw(); $data = $response->json(); if (! is_array($data)) throw new \UnexpectedValueException('Ugyldig JSON.'); return $data; }
}
