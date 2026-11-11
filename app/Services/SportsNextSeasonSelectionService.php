<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SportsNextSeasonSelectionService
{
    public function effectiveSeasonId(string $key, int $default): int
    {
        if (! Schema::hasTable('sportsnext_season_selections')) return $default;
        $selected = DB::table('sportsnext_season_selections')->where('competition_key', $key)->value('selected_season_id');
        return is_numeric($selected) ? (int) $selected : $default;
    }

    public function row(string $key, int $default): ?object
    {
        return Schema::hasTable('sportsnext_season_selections')
            ? DB::table('sportsnext_season_selections')->where('competition_key', $key)->first() : null;
    }

    public function setAutomatic(string $key, bool $enabled): void
    {
        $this->upsert($key, ['automatic_enabled' => $enabled, 'blocked_candidate_season_id' => $enabled ? null : ($this->row($key, 0)?->blocked_candidate_season_id)]);
    }

    public function select(string $key, int $seasonId, string $method, string $reason, bool $blockCandidate = false, ?int $fromOverride = null): void
    {
        DB::transaction(function () use ($key, $seasonId, $method, $reason, $blockCandidate, $fromOverride) {
            $row = DB::table('sportsnext_season_selections')->where('competition_key', $key)->lockForUpdate()->first();
            $from = $row?->selected_season_id ?? $fromOverride;
            $this->upsert($key, [
                'selected_season_id' => $seasonId,
                'automatic_enabled' => $blockCandidate ? false : ($row?->automatic_enabled ?? false),
                'blocked_candidate_season_id' => $blockCandidate ? ($row?->candidate_season_id ?? $from) : ($row?->blocked_candidate_season_id ?? null),
                'candidate_season_id' => null, 'candidate_name' => null, 'candidate_start_date' => null, 'candidate_end_date' => null,
                'candidate_observations' => 0, 'candidate_first_observed_at' => null, 'candidate_last_observed_at' => null,
                'status' => 'Byttet', 'status_reason' => $reason, 'notice_read_at' => null,
            ]);
            DB::table('sportsnext_season_switches')->insert(['competition_key' => $key, 'from_season_id' => $from, 'to_season_id' => $seasonId, 'method' => $method, 'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function upsert(string $key, array $values): void
    {
        if (! Schema::hasTable('sportsnext_season_selections')) return;
        DB::table('sportsnext_season_selections')->updateOrInsert(['competition_key' => $key], $values + ['updated_at' => now(), 'created_at' => now()]);
    }
}
