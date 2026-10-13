<?php

namespace App\Services;

use App\ExternalDataFailureDigest;
use App\ExternalDataSourceEvent;
use App\Mail\ExternalDataFailureDigestMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ExternalDataFailureDigestService
{
    public function send(?Carbon $end = null): bool
    {
        if (! Schema::hasTable('external_data_source_events') || ! Schema::hasTable('external_data_failure_digests')) {
            return false;
        }

        $end = ($end ?: now('Europe/Oslo'))->copy()->setTimezone('Europe/Oslo');
        $digest = $this->claim($end);
        if (! $digest) {
            return false;
        }

        $groups = $this->groups($digest->period_starts_at, $digest->period_ends_at);
        if ($groups === []) {
            $digest->update(['status' => 'sent', 'sent_at' => now(), 'locked_at' => null]);
            return false;
        }

        $recipient = config('feedback.notification_email');
        if (! is_string($recipient) || trim($recipient) === '') {
            $digest->update(['status' => 'failed', 'locked_at' => null]);
            Log::warning('External data failure digest has no configured recipient.');
            return false;
        }

        try {
            Mail::to($recipient)->send(new ExternalDataFailureDigestMail(
                $groups,
                $digest->period_starts_at->copy()->setTimezone('Europe/Oslo')->format('Y-m-d H:i:s T'),
                $digest->period_ends_at->copy()->setTimezone('Europe/Oslo')->format('Y-m-d H:i:s T'),
            ));
            $digest->update(['status' => 'sent', 'sent_at' => now(), 'locked_at' => null]);
            return true;
        } catch (Throwable $exception) {
            // Keep both source events and the digest retryable when mail transport fails.
            $digest->update(['status' => 'failed', 'locked_at' => null]);
            Log::warning('External data failure digest email could not be sent.', ['exception' => get_class($exception)]);
            return false;
        }
    }

    public function prune(): int
    {
        if (! Schema::hasTable('external_data_source_events')) {
            return 0;
        }

        return ExternalDataSourceEvent::where('occurred_at', '<', now()->subDays((int) config('external_data.retention_days', 90)))->delete();
    }

    private function claim(Carbon $end): ?ExternalDataFailureDigest
    {
        $key = $end->format('Y-m-d');
        return DB::transaction(function () use ($end, $key) {
            $digest = ExternalDataFailureDigest::lockForUpdate()->firstOrCreate(
                ['digest_key' => $key],
                ['period_starts_at' => $end->copy()->subDay(), 'period_ends_at' => $end, 'status' => 'pending']
            );
            $digest->refresh();
            if ($digest->status === 'sent') {
                return null;
            }
            if ($digest->status === 'sending' && $digest->locked_at && $digest->locked_at->gt(now()->subMinutes(30))) {
                return null;
            }
            $digest->update(['status' => 'sending', 'locked_at' => now()]);
            return $digest->fresh();
        });
    }

    private function groups(Carbon $start, Carbon $end): array
    {
        $events = ExternalDataSourceEvent::where('outcome', ExternalDataSourceEvent::OUTCOME_FAILURE)
            ->where('occurred_at', '>=', $start)->where('occurred_at', '<=', $end)->orderBy('occurred_at')->get();

        return $events->groupBy(fn ($event) => $event->service.'|'.$event->operation.'|'.$event->failure_kind)
            ->map(function ($events) {
                $first = $events->first();
                $last = $events->last();
                $recovered = ExternalDataSourceEvent::where('outcome', ExternalDataSourceEvent::OUTCOME_SUCCESS)
                    ->where('service', $last->service)->where('operation', $last->operation)
                    ->where(function ($query) use ($last) {
                        $query->where('occurred_at', '>', $last->occurred_at)
                            ->orWhere(function ($query) use ($last) {
                                $query->where('occurred_at', '=', $last->occurred_at)->where('id', '>', $last->id);
                            });
                    })->exists();
                return [
                    'service' => $last->service, 'operation' => $last->operation, 'failure_kind' => $last->failure_kind,
                    'count' => $events->count(),
                    'first_at' => $first->occurred_at->copy()->setTimezone('Europe/Oslo')->format('Y-m-d H:i:s T'),
                    'last_at' => $last->occurred_at->copy()->setTimezone('Europe/Oslo')->format('Y-m-d H:i:s T'),
                    'summary' => $first->summary,
                    'recovery' => $recovered ? 'Vellykket oppdatering etter siste feil' : 'Status fortsatt uavklart',
                ];
            })->values()->all();
    }
}
