<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only summary of the source checks recorded by the scheduled jobs. */
class AdminExternalDataStatusService
{
    public function dashboard(): array
    {
        $checks = Schema::hasTable('external_data_checks')
            ? DB::table('external_data_checks')->whereNotIn('service', ['tv-guide-vg', 'tv-guide-vg-channel'])->get()
            : collect();

        $rows = [];
        foreach (config('prisons.all', []) as $slug => $prison) {
            $name = $prison['name'] ?? $slug;
            $rows[] = $this->row('Værvarsel', $name, (bool) ($prison['features']['weather'] ?? false), $checks->where('service', 'met-weather')->where('coverage_key', $slug));
            $rows[] = $this->row('Lokale nyheter', $name, (bool) ($prison['features']['local_news'] ?? false), $checks->filter(fn ($check) => str_starts_with($check->service, 'local-news-'.$slug.'-')));

            $location = $prison['prayer_location_id'] ?? null;
            $hasLocation = is_int($location) || ctype_digit((string) $location);
            $rows[] = $this->row('Bønnetider', $name, (bool) ($prison['features']['prayer'] ?? false) && $hasLocation, $checks->where('service', 'bonnetid-no')->filter(fn ($check) => str_starts_with($check->coverage_key, $location.'-')));
        }

        $rows[] = $this->row('Fotballdata', null, true, $checks->where('service', 'sportsnext'));
        $rows[] = $this->row('Navnedager', null, true, $checks->where('service', 'namedays-webapi'));
        $rows[] = $this->row('Dagen i dag', null, true, $checks->filter(fn ($check) => in_array($check->service, ['wikipedia-on-this-day', 'wikidata-entities'], true)));
        $rows[] = $this->row('Kriminalomsorgsnyheter', null, true, $checks->filter(fn ($check) => str_starts_with($check->service, 'correctional-news-')));

        return $rows;
    }

    private function row(string $service, ?string $prison, bool $enabled, $checks): array
    {
        $latestAttempt = $checks->filter(fn ($check) => $check->last_check_at)->sortByDesc('last_check_at')->first();
        $latestSuccess = $checks->filter(fn ($check) => $check->last_success_at)->sortByDesc('last_success_at')->first();
        $missing = $checks->contains(fn ($check) => (int) $check->failure_count > 0 && ! (bool) $check->usable);
        $fallback = ! $missing && $checks->contains(fn ($check) => (int) $check->failure_count > 0 && (bool) $check->usable);

        [$status, $detail] = ! $enabled
            ? ['Deaktivert', 'Tjenesten er deaktivert i fengselsregisteret.']
            : ($checks->isEmpty()
                ? ['Ikke registrert', 'Ingen lagret kontrollhistorikk ennå.']
                : ($missing
                    ? ['Data mangler', 'Minst én kontroll mangler brukbare data og trenger oppfølging.']
                    : ($fallback
                        ? ['Brukbare reservedata', 'Siste kildeforsøk har feil, men cache eller reserve gir fortsatt brukbare data.']
                        : ['Kilden svarer', 'Siste registrerte kildeforsøk var vellykket.'])));

        return [
            'service' => $service,
            'prison' => $prison,
            'enabled' => $enabled,
            'checks' => $checks,
            'last_attempt_at' => $latestAttempt?->last_check_at ? Carbon::parse($latestAttempt->last_check_at)->timezone('Europe/Oslo') : null,
            'last_success_at' => $latestSuccess?->last_success_at ? Carbon::parse($latestSuccess->last_success_at)->timezone('Europe/Oslo') : null,
            'status' => $status,
            'detail' => $detail,
            'cadence' => $this->cadence(),
        ];
    }

    private function cadence(): string
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'external-data:check-others'));

        return $event && $event->expression === '*/5 * * * *'
            ? 'Scheduler hvert 5. minutt; kilde hentes ved behov.'
            : 'Se planlagt scheduler.';
    }
}
