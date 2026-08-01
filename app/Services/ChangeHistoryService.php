<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\Process\Process;
use Throwable;

class ChangeHistoryService
{
    private const CACHE_KEY = 'public-change-history.v1';

    private CacheRepository $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function get(): array
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            max(1, (int) config('change-history.cache_ttl', 3600)),
            fn () => $this->readHistory()
        );
    }

    private function readHistory(): array
    {
        $repositoryPath = $this->repositoryPath();

        if ($repositoryPath === null) {
            return $this->unavailableResult();
        }

        try {
            $process = new Process([
                'git', '-C', $repositoryPath, 'log', '--no-merges',
                '--format=%H%x1f%cI%x1f%s%x1e',
            ]);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                return $this->unavailableResult();
            }

            $changes = $this->parse($process->getOutput());

            if ($changes === []) {
                return $this->unavailableResult();
            }

            return [
                'available' => true,
                'updated_at' => $changes[0]['date'],
                'groups' => collect($changes)
                    ->groupBy(fn (array $change) => $change['date']->format('Y-m-d'))
                    ->map(fn ($items) => $items->values()->all())
                    ->all(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableResult();
        }
    }

    private function repositoryPath(): ?string
    {
        $configuredPath = config('change-history.repository_path');
        $path = $configuredPath ?: base_path();

        if (!is_string($path) || !$this->isAbsolutePath($path)) {
            return null;
        }

        $realPath = realpath($path);

        return $realPath !== false && is_dir($realPath.DIRECTORY_SEPARATOR.'.git')
            ? $realPath
            : null;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function parse(string $output): array
    {
        $changes = [];

        foreach (explode("\x1e", $output) as $record) {
            $fields = explode("\x1f", trim($record));

            if (count($fields) !== 3 || trim($fields[2]) === '') {
                continue;
            }

            try {
                $date = CarbonImmutable::parse($fields[1])->locale('nb');
            } catch (Throwable $exception) {
                continue;
            }

            $changes[] = [
                'date' => $date,
                'message' => trim($fields[2]),
            ];
        }

        return $changes;
    }

    private function unavailableResult(): array
    {
        return ['available' => false, 'updated_at' => null, 'groups' => []];
    }
}
