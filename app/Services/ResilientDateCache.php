<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ResilientDateCache
{
    public function remember(string $key, int $ttl, Closure $fetch, array $fallback = []): array
    {
        $freshKey = 'today.fresh.'.$key;
        $staleKey = 'today.stale.'.$key;

        if (Cache::has($freshKey)) {
            return Cache::get($freshKey, $fallback);
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
            report($exception);

            return Cache::get($staleKey, $fallback);
        }
    }
}
