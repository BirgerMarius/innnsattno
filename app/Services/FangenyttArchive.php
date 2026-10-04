<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FangenyttArchive
{
    public const DISK = 'fangenytt';

    public function __construct(private FangenyttCoverGenerator $coverGenerator)
    {
    }

    public function issues(): array
    {
        $disk = Storage::disk(self::DISK);

        return array_values(array_map(function (array $issue) use ($disk) {
            $issue['cover_file'] = $this->coverFile($issue);
            $issue['has_cover'] = $disk->exists($issue['cover_file']);

            return $issue;
        }, array_filter(config('fangenytt.issues', []), fn (array $issue) => $this->hasExpectedLocalFile($issue))));
    }

    public function findIssue(int $number): ?array
    {
        foreach (config('fangenytt.issues', []) as $issue) {
            if ((int) ($issue['number'] ?? 0) === $number && $this->hasExpectedLocalFile($issue)) {
                return $issue;
            }
        }

        return null;
    }

    public function sync(bool $force = false): array
    {
        $reports = [];

        foreach (config('fangenytt.issues', []) as $issue) {
            $number = $issue['number'] ?? '?';

            if (! $this->hasExpectedLocalFile($issue) || empty($issue['original_url'])) {
                $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Ugyldig Fangenytt-konfigurasjon.'];
                continue;
            }

            $disk = Storage::disk(self::DISK);
            if (! $force && $disk->exists($issue['local_file'])) {
                $reports[] = ['number' => $number, 'status' => 'hoppet over', 'message' => 'Lokal fil finnes allerede.'];
                continue;
            }

            try {
                $response = Http::accept('application/pdf')
                    ->connectTimeout(5)
                    ->timeout(30)
                    ->get($issue['original_url']);

                if (! $response->successful()) {
                    $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'HTTP-status '.$response->status().'.'];
                    continue;
                }

                $contents = $response->body();
                if (! $this->looksLikePdf($contents)) {
                    $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Responsen er ikke en PDF.'];
                    continue;
                }

                $disk->put($issue['local_file'], $contents);
                $reports[] = ['number' => $number, 'status' => 'lastet ned', 'message' => $issue['local_file']];
                $reports[] = $this->generateCover($issue, true);
            } catch (Throwable $exception) {
                report($exception);
                $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Kunne ikke hente PDF-en.'];
            }
        }

        return $reports;
    }

    public function generateCovers(bool $force = false): array
    {
        $reports = [];

        foreach (config('fangenytt.issues', []) as $issue) {
            $number = $issue['number'] ?? '?';

            if (! $this->hasExpectedLocalFile($issue)) {
                $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Ugyldig Fangenytt-konfigurasjon.'];
                continue;
            }

            $reports[] = $this->generateCover($issue, $force);
        }

        return $reports;
    }

    public function coverFile(array $issue): string
    {
        return 'covers/fangenytt-'.$issue['number'].'.jpg';
    }

    private function generateCover(array $issue, bool $force): array
    {
        $disk = Storage::disk(self::DISK);
        $number = $issue['number'];
        $coverFile = $this->coverFile($issue);

        if (! $disk->exists($issue['local_file'])) {
            return ['number' => $number, 'status' => 'hoppet over', 'message' => 'Lokal PDF mangler.'];
        }

        if (! $force && $disk->exists($coverFile)) {
            return ['number' => $number, 'status' => 'hoppet over', 'message' => 'Forside finnes allerede.'];
        }

        $result = $this->coverGenerator->generate($issue['local_file'], $coverFile);

        return [
            'number' => $number,
            'status' => $result['success'] ? 'forside generert' : 'feilet',
            'message' => $result['message'],
        ];
    }

    private function hasExpectedLocalFile(array $issue): bool
    {
        $number = filter_var($issue['number'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $number !== false
            && isset($issue['local_file'])
            && $issue['local_file'] === 'fangenytt-'.$number.'.pdf';
    }

    private function looksLikePdf(string $contents): bool
    {
        return strpos(substr($contents, 0, 1024), '%PDF-') !== false;
    }
}
