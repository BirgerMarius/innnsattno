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
        if (! Schema::hasTable('external_data_checks')) return [];

        return DB::table('external_data_checks')->where('service', 'tv-guide-vg-channel')
            ->where('failure_count', '>', 0)->orderBy('first_failure_at')->get()->map(function (object $row): array {
                [$date, $slug] = explode('|', $row->coverage_key, 2) + [null, null];
                $error = (string) $row->last_error;
                $source = str_contains($error, 'Strim') ? 'Strim' : (str_contains($error, 'lokal cache') ? 'tidligere lokal cache' : 'ingen brukbar reserve');
                $first = Carbon::parse($row->first_failure_at)->setTimezone('Europe/Oslo');
                $last = Carbon::parse($row->last_check_at)->setTimezone('Europe/Oslo');
                $prisons = collect(config('prisons.all', []))->filter(fn (array $prison) => in_array($slug, $prison['tv_channels'] ?? [], true))->pluck('name')->all();

                return compact('date', 'slug', 'source', 'first', 'last', 'prisons');
            })->all();
    }
}
