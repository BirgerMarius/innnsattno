<?php

namespace App\Services;

use App\FangenyttIssue;
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

        return FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)
            ->orderByDesc('number')
            ->get()
            ->map(function (FangenyttIssue $issue) use ($disk) {
                $issue->has_cover = $disk->exists($issue->cover_file);

                return $issue;
            })
            ->all();
    }

    public function findIssue(int $number): ?FangenyttIssue
    {
        return FangenyttIssue::where('number', $number)
            ->where('status', FangenyttIssue::STATUS_PUBLISHED)
            ->first();
    }

    public function sync(bool $force = false): array
    {
        $reports = [];

        foreach (FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)->get() as $issue) {
            $number = $issue->number;

            $disk = Storage::disk(self::DISK);
            if (! $force && $disk->exists($issue->local_file)) {
                $reports[] = ['number' => $number, 'status' => 'hoppet over', 'message' => 'Lokal fil finnes allerede.'];
                continue;
            }

            try {
                $response = Http::accept('application/pdf')
                    ->connectTimeout(5)
                    ->timeout(30)
                    ->get($issue->original_url);

                if (! $response->successful()) {
                    $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'HTTP-status '.$response->status().'.'];
                    continue;
                }

                $contents = $response->body();
                if (! $this->looksLikePdf($contents)) {
                    $reports[] = ['number' => $number, 'status' => 'feilet', 'message' => 'Responsen er ikke en PDF.'];
                    continue;
                }

                $disk->put($issue->local_file, $contents);
                $reports[] = ['number' => $number, 'status' => 'lastet ned', 'message' => $issue->local_file];
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

        foreach (FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)->get() as $issue) {
            $reports[] = $this->generateCover($issue, $force);
        }

        return $reports;
    }

    public function coverFile(FangenyttIssue $issue): string
    {
        return $issue->cover_file;
    }

    private function generateCover(FangenyttIssue $issue, bool $force): array
    {
        $disk = Storage::disk(self::DISK);
        $number = $issue->number;
        $coverFile = $this->coverFile($issue);

        if (! $disk->exists($issue->local_file)) {
            return ['number' => $number, 'status' => 'hoppet over', 'message' => 'Lokal PDF mangler.'];
        }

        if (! $force && $disk->exists($coverFile)) {
            return ['number' => $number, 'status' => 'hoppet over', 'message' => 'Forside finnes allerede.'];
        }

        $result = $this->coverGenerator->generate($issue->local_file, $coverFile);

        return [
            'number' => $number,
            'status' => $result['success'] ? 'forside generert' : 'feilet',
            'message' => $result['message'],
        ];
    }

    private function looksLikePdf(string $contents): bool
    {
        return strpos(substr($contents, 0, 1024), '%PDF-') !== false;
    }
}
