<?php

namespace App\Services;

use App\Mail\ExternalDataIncidentMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** State is per source and data coverage, never per page view or cache hit. */
class ExternalDataMonitor
{
    public function checked(string $service, string $key, string $type, string $description, bool $essential, bool $success, bool $usable, ?string $error = null, ?Carbon $activeUntil = null): void
    {
        try {
            if (! Schema::hasTable('external_data_checks')) {
                return;
            }
            DB::transaction(function () use ($service, $key, $type, $description, $essential, $success, $usable, $error, $activeUntil) {
                $query = DB::table('external_data_checks')->where('service', $service)->where('coverage_key', $key);
                // The caller holds the shared coverage lock, including during insertion.
                $row = $query->lockForUpdate()->first();
                $now = now();
                $gapExpired = $row && $row->last_check_at && Carbon::parse($row->last_check_at)->lt($now->copy()->subMinutes((int) config('external_data.max_check_gap_minutes', 60)));
                $values = [
                    'datatype' => $type, 'description' => $description, 'essential' => $essential,
                    'active_until' => $activeUntil,
                    'usable' => $usable, 'last_check_at' => $now, 'updated_at' => $now,
                    'last_success_at' => $success ? $now : ($row->last_success_at ?? null),
                    'first_failure_at' => $success ? null : ($gapExpired ? $now : ($row->first_failure_at ?? $now)),
                    'failure_count' => $success ? 0 : ($gapExpired ? 1 : (($row->failure_count ?? 0) + 1)),
                    'last_error' => $success ? null : mb_substr((string) $error, 0, 500),
                ];
                if ($row) {
                    $query->update($values);
                } else {
                    DB::table('external_data_checks')->insert($values + ['service' => $service, 'coverage_key' => $key, 'created_at' => $now]);
                }
            });
        } catch (Throwable $exception) {
            // A monitoring database problem must not turn a successful source refresh into a source failure.
            Log::warning('External data check could not be recorded.', ['service' => $service, 'exception' => get_class($exception)]);
        }
    }

    /** Called by the scheduler, under withoutOverlapping; mail failures remain retryable. */
    public function evaluate(): void
    {
        if (! Schema::hasTable('external_data_checks') || ! Schema::hasTable('external_data_incidents')) {
            return;
        }
        $lock = Cache::lock('external-data.evaluate', 120);
        if (! $lock->get()) {
            return;
        }
        try {
            $this->evaluateSources();
        } finally {
            $lock->release();
        }
    }

    private function evaluateSources(): void
    {
        $services = DB::table('external_data_checks')->distinct()->pluck('service');
        foreach ($services as $service) {
            $rows = DB::table('external_data_checks')->where('service', $service)->get()->keyBy('coverage_key');
            $incident = DB::table('external_data_incidents')->where('service', $service)->first();
            $critical = $rows->filter(fn ($row) => $this->isCritical($row));
            $keys = $incident ? json_decode($incident->affected_keys, true) : [];
            $keys = is_array($keys) ? $keys : [];
            if ($incident && $critical->isEmpty()) {
                // A recovery is about the availability that triggered the email.
                // The primary source may still be failing while a validated reserve
                // or local cache has restored every affected coverage.
                $recovered = collect($keys)->isNotEmpty()
                    && collect($keys)->every(fn ($key) => isset($rows[$key]) && (bool) $rows[$key]->usable);
                if ($recovered && $this->send($service, 'recovered', $rows->only($keys)->values()->all())) {
                    DB::table('external_data_incidents')->where('id', $incident->id)->delete();
                }
                continue;
            }
            if ($critical->isEmpty()) {
                continue;
            }
            $allKeys = array_values(array_unique(array_merge($keys, $critical->keys()->all())));
            $due = ! $incident || ! $incident->last_notified_at || Carbon::parse($incident->last_notified_at)->lte(now()->subMinutes((int) config('external_data.reminder_minutes', 1440)));
            if ($due && $this->send($service, $incident ? 'reminder' : 'critical', $critical->values()->all())) {
                DB::table('external_data_incidents')->updateOrInsert(['service' => $service], [
                    'affected_keys' => json_encode($allKeys), 'last_notified_at' => now(), 'updated_at' => now(), 'created_at' => $incident->created_at ?? now(),
                ]);
            } elseif ($incident && $allKeys !== $keys) {
                DB::table('external_data_incidents')->where('id', $incident->id)->update(['affected_keys' => json_encode($allKeys), 'updated_at' => now()]);
            }
        }
    }

    private function isCritical(object $row): bool
    {
        if (! $row->first_failure_at || ! $row->last_check_at || (int) $row->failure_count === 0) {
            return false;
        }
        if (($row->active_until && Carbon::parse($row->active_until)->lt(now())) || Carbon::parse($row->last_check_at)->lt(now()->subMinutes((int) config('external_data.max_check_gap_minutes', 60)))) {
            return false;
        }
        // `usable` is the service's validated answer to whether any permitted
        // primary, reserve or local source currently covers this exact key.
        // Keep the failed check and its counters for operations, but do not
        // page for a source failure while the data remains available.
        if ((bool) $row->usable) {
            return false;
        }
        // The selection-wide VG row is operational diagnostics only. TV
        // availability is evaluated by the date|channel rows so one working
        // channel can never hide a missing one (or create a duplicate alert).
        if ($row->service === 'tv-guide-vg') {
            return false;
        }
        $duration = Carbon::parse($row->first_failure_at)->diffInMinutes(Carbon::parse($row->last_check_at));
        $missing = ! (bool) $row->usable;
        if ($missing && $row->essential && $row->failure_count >= (int) config('external_data.missing_count', 3) && $duration >= (int) config('external_data.missing_minutes', 10)) {
            return true;
        }
        // Prayer times are date-specific, rather than rendered obsolete by age.
        if (in_array($row->datatype, ['prayer', 'calendar'], true)) {
            return $missing && $row->failure_count >= (int) config('external_data.failure_count', 5) && $duration >= (int) config('external_data.failure_minutes', 60);
        }
        $maxAge = config('external_data.max_age_minutes.'.$row->datatype);
        if ($maxAge === null || (! $row->essential && $missing && $row->datatype === 'tv')) {
            return false;
        }
        $old = $missing || ! $row->last_success_at || Carbon::parse($row->last_success_at)->lte(now()->subMinutes((int) $maxAge));
        return $old && $row->failure_count >= (int) config('external_data.failure_count', 5) && $duration >= (int) config('external_data.failure_minutes', 60);
    }

    private function send(string $service, string $state, array $rows): bool
    {
        $recipient = config('feedback.notification_email');
        if (! is_string($recipient) || trim($recipient) === '') {
            Log::warning('External data incident has no configured recipient.');
            return false;
        }
        try {
            Mail::to($recipient)->send(new ExternalDataIncidentMail($service, $state, $rows));
            return true;
        } catch (Throwable $exception) {
            Log::warning('External data incident email could not be sent.', ['exception' => get_class($exception)]);
            return false;
        }
    }
}
