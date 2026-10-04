<?php

namespace App\Services;

use App\FangenyttIssue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FangenyttImporter
{
    public function __construct(private FangenyttCoverGenerator $coverGenerator)
    {
    }

    public function import(array $candidate): array
    {
        $validation = $this->validateCandidate($candidate);
        if ($validation !== null) {
            return ['success' => false, 'message' => $validation];
        }

        $number = (int) $candidate['number'];
        $existing = FangenyttIssue::where('number', $number)->first();
        if ($existing && $existing->status !== FangenyttIssue::STATUS_FAILED) {
            return ['success' => false, 'message' => 'Fangenytt nr. '.$number.' er allerede registrert.'];
        }

        $localFile = 'fangenytt-'.$number.'.pdf';
        $coverFile = 'covers/fangenytt-'.$number.'.jpg';
        $disk = Storage::disk(FangenyttArchive::DISK);
        if (! $existing && ($disk->exists($localFile) || $disk->exists($coverFile))) {
            return ['success' => false, 'message' => 'Lokale filer finnes allerede uten registrert utgave.'];
        }

        $issue = $existing ?: new FangenyttIssue(['number' => $number]);
        $issue->fill([
            'edition' => $candidate['edition'] ?? null,
            'published_at' => $candidate['published_at'] ?? null,
            'original_url' => $candidate['original_url'],
            'local_file' => $localFile,
            'cover_file' => $coverFile,
            'source' => $candidate['source'] ?? 'manual',
            'status' => FangenyttIssue::STATUS_PENDING,
        ]);
        $issue->save();

        try {
            $response = Http::accept('application/pdf')
                ->connectTimeout(5)
                ->timeout(30)
                ->get($issue->original_url);

            if (! $response->successful()) {
                return $this->fail($issue, 'HTTP-status '.$response->status().'.');
            }

            $contents = $response->body();
            if (! $this->looksLikePdf($contents)) {
                return $this->fail($issue, 'Responsen er ikke en PDF.');
            }

            $disk->put($localFile, $contents);
            if (! $disk->exists($localFile) || ! $this->looksLikePdf($disk->get($localFile))) {
                return $this->fail($issue, 'Den lokale PDF-en kunne ikke leses.');
            }

            $cover = $this->coverGenerator->generate($localFile, $coverFile);
            if (! $cover['success'] || ! $disk->exists($coverFile)) {
                return $this->fail($issue, 'Forsiden kunne ikke genereres.');
            }

            $issue->status = FangenyttIssue::STATUS_PUBLISHED;
            $issue->save();

            return ['success' => true, 'message' => 'Fangenytt nr. '.$number.' er importert og publisert.', 'issue' => $issue];
        } catch (Throwable $exception) {
            report($exception);

            return $this->fail($issue, 'Kunne ikke hente PDF-en.');
        }
    }

    private function fail(FangenyttIssue $issue, string $message): array
    {
        $disk = Storage::disk(FangenyttArchive::DISK);
        $disk->delete([$issue->local_file, $issue->cover_file]);
        $issue->status = FangenyttIssue::STATUS_FAILED;
        $issue->save();

        return ['success' => false, 'message' => $message, 'issue' => $issue];
    }

    private function validateCandidate(array &$candidate): ?string
    {
        $number = filter_var($candidate['number'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($number === false) {
            return 'Utgavenummer må være et positivt heltall.';
        }

        $url = $candidate['original_url'] ?? '';
        $parts = is_string($url) ? parse_url($url) : false;
        $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ($parts['scheme'] ?? null) !== 'https'
            || ! in_array($host, config('fangenytt.allowed_source_hosts', []), true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port']) && (int) $parts['port'] !== 443) {
            return 'Original-URL-en må være en HTTPS-lenke fra en godkjent Fangeforeningen-kilde.';
        }

        $source = $candidate['source'] ?? 'manual';
        if (! is_string($source) || ! preg_match('/\A[a-z0-9_-]{1,50}\z/i', $source)) {
            return 'Kilde må være en kort teknisk identifikator.';
        }

        if (! empty($candidate['published_at'])) {
            try {
                $candidate['published_at'] = Carbon::parse($candidate['published_at']);
            } catch (Throwable $exception) {
                return 'Publiseringsdatoen er ugyldig.';
            }
        }

        $candidate['number'] = $number;

        return null;
    }

    private function looksLikePdf(string $contents): bool
    {
        return strpos(substr($contents, 0, 1024), '%PDF-') !== false;
    }
}
