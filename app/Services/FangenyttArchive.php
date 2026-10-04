<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FangenyttArchive
{
    public const DISK = 'fangenytt';

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
            } catch (Throwable $exception) {
                report($exception);
                $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Kunne ikke hente PDF-en.'];
            }
        }

        return $reports;
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
