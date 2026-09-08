<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ResilientDateCache
{
    public function remember(
        string $key,
        int $ttl,
        Closure $fetch,
        array $fallback = [],
        ?Closure $onFailure = null,
        int $failureTtl = 0,
    ): array
    {
        $freshKey = 'today.fresh.'.$key;
        $staleKey = 'today.stale.'.$key;
        $failureKey = 'today.failure.'.$key;

        if (Cache::has($freshKey)) {
            return Cache::get($freshKey, $fallback);
        }

        // A failed source should not be retried on every page view.  The key is
        // derived from the caller's date-specific key, so data from another
        // date can never become this date's fallback.
        if ($failureTtl > 0 && Cache::has($failureKey)) {
            return Cache::get($staleKey, $fallback);
        }

        try {
            $data = $fetch();

            if (!is_array($data) || $data === []) {
                throw new \RuntimeException('Datakilden returnerte ingen brukbare data.');
            }

            Cache::put($freshKey, $data, $ttl);
            Cache::forever($staleKey, $data);

            return $data;
        } catch (Throwable $exception) {
            if ($failureTtl > 0) {
                Cache::put($failureKey, true, $failureTtl);
            }

            if ($onFailure !== null) {
                $onFailure($exception);
            } else {
                report($exception);
            }

            return Cache::get($staleKey, $fallback);
        }
    }
}
