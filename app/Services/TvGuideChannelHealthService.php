<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TvGuideChannelHealthService
{
    /** Active VG channel faults; the monitor owns first/last observation timestamps. */
    public function active(): array
    {
        return $this->openIssues();
    }

    /**
     * Prepare the date-scoped channel incidents for the administrator.  This
     * deliberately does not interpret an expired programme date as recovery:
     * only the monitor can clear a VG incident after a successful VG check.
     */
    public function dashboard(): array
    {
        $today = now('Europe/Oslo')->toDateString();
        $issues = $this->openIssues();

        $groups = [
            'today' => [],
            'upcoming' => [],
            'previous' => [],
        ];
        foreach ($issues as $issue) {
            $group = $issue['date'] === $today ? 'today' : ($issue['date'] > $today ? 'upcoming' : 'previous');
            $groups[$group][] = $issue;
        }

        usort($groups['upcoming'], fn (array $left, array $right) => [$left['date'], $left['name']] <=> [$right['date'], $right['name']]);
        usort($groups['previous'], fn (array $left, array $right) => [$right['date'], $left['name']] <=> [$left['date'], $right['name']]);

        $todayChannels = collect($groups['today'])->unique('slug')->values();
        $affectedPrisons = $todayChannels->pluck('prisons')->flatten()->unique()->values()->all();

        return $groups + [
            'todaySummary' => [
                'channelCount' => $todayChannels->count(),
                'missingCount' => $todayChannels->where('source', 'missing')->count(),
                'prisons' => $affectedPrisons,
            ],
        ];
    }

    private function openIssues(): array
    {
        if (! Schema::hasTable('external_data_checks')) return [];

        return DB::table('external_data_checks')->where('service', 'tv-guide-vg-channel')
            ->where('failure_count', '>', 0)->orderBy('first_failure_at')->get()->map(function (object $row): array {
                [$date, $slug] = explode('|', $row->coverage_key, 2) + [null, null];
                $error = (string) $row->last_error;
                $source = str_contains($error, 'Strim') ? 'strim' : (str_contains($error, 'lokal cache') ? 'local' : 'missing');
                $first = Carbon::parse($row->first_failure_at)->setTimezone('Europe/Oslo');
                $last = Carbon::parse($row->last_check_at)->setTimezone('Europe/Oslo');
                $prisons = collect(config('prisons.all', []))->filter(fn (array $prison) => in_array($slug, $prison['tv_channels'] ?? [], true))->pluck('name')->all();
                $name = config('tv_guide.strim_channels.'.$slug.'.name', $slug);
                $programStatus = match ($source) {
                    'strim' => 'Programmer tilgjengelige fra Strim',
                    'local' => 'Bruker tidligere hentede programmer for samme dato',
                    default => 'Programdata mangler',
                };

                return compact('date', 'slug', 'name', 'source', 'programStatus', 'first', 'last', 'prisons');
            })->all();
    }
}
