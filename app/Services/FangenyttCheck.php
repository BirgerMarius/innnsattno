<?php

namespace App\Services;

use App\FangenyttIssue;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class FangenyttCheck
{
    public const SOURCE = 'https://www.fangeforeningen.no/nyhetsbrev';
    private const MAX_SIZE = 52428800;

    public function status(): array
    {
        $row = DB::table('fangenytt_check_status')->where('id', 1)->first();
        $success = $row && $row->last_success_at ? Carbon::parse($row->last_success_at) : null;
        $attempt = $row && $row->last_attempt_at ? Carbon::parse($row->last_attempt_at) : null;
        $next = $row && $row->last_error && $attempt ? $attempt->copy()->addDay() : ($success ? $success->copy()->addDays(14) : null);
        return ['success' => $success, 'next' => $next, 'error' => $row->last_error ?? null,
            'latest' => FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)->orderByDesc('number')->first(),
            'announcement' => FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)->whereNotNull('published_locally_at')->orderByDesc('published_locally_at')->first()];
    }

    public function run(bool $force = false): array
    {
        $disk = Storage::disk(FangenyttArchive::DISK);
        $disk->makeDirectory('.');
        $lock = fopen($disk->path('.check.lock'), 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            return ['success' => false, 'message' => 'En kontroll kjører allerede.'];
        }
        try {
            $status = $this->status();
            if (! $force && $status['next'] && now()->lt($status['next'])) {
                return ['success' => true, 'message' => 'Kontrollen er ikke forfalt.'];
            }
            DB::table('fangenytt_check_status')->updateOrInsert(['id' => 1], ['last_attempt_at' => now(), 'updated_at' => now()]);
            try {
                $response = Http::connectTimeout(5)->timeout(30)->withOptions(['allow_redirects' => false])->get(self::SOURCE);
                if (! $response->successful()) throw new \RuntimeException('Kildesiden kunne ikke hentes.');
                $candidates = $this->candidates($response->body());
                if (! $candidates) throw new \RuntimeException('Fant ingen gjenkjennelige utgaver på kildesiden.');
                $latest = FangenyttIssue::where('status', FangenyttIssue::STATUS_PUBLISHED)->max('number');
                if (! $latest) throw new \RuntimeException('Eksisterende arkiv er ikke registrert.');
                foreach ($candidates as $number => $url) {
                    if ($number <= $latest) continue;
                    $this->publish($number, $url);
                }
                DB::table('fangenytt_check_status')->where('id', 1)->update(['last_success_at' => now(), 'last_error' => null, 'updated_at' => now()]);
                return ['success' => true, 'message' => 'Kontrollen er fullført.'];
            } catch (Throwable $e) {
                DB::table('fangenytt_check_status')->where('id', 1)->update(['last_error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function candidates(string $html): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $result = [];
        foreach ((new DOMXPath($dom))->query('//a[@href] | //iframe[@src] | //embed[@src] | //object[@data]') as $node) {
            $url = $node->getAttribute($node->tagName === 'a' ? 'href' : ($node->tagName === 'object' ? 'data' : 'src'));
            $parts = parse_url($url);
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ! in_array(strtolower($parts['host'] ?? ''), config('fangenytt.allowed_source_hosts', []), true) || isset($parts['user']) || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])) continue;
            $name = rawurldecode(basename($parts['path'] ?? ''));
            if (! preg_match('/^(?:fangenytt|nyhetsbrev)[\s._-]+(?:(?:magasin|nr)[\s._-]*)?(\d+)(?:[\s._-].*)?\.pdf$/i', $name, $m)) {
                if (preg_match('/\.pdf$/i', $name) && ! FangenyttIssue::where('original_url', $url)->exists()) throw new \RuntimeException('Fant en PDF-lenke med ukjent utgavenummer.');
                continue;
            }
            $number = (int) $m[1];
            if ($number < 1 || $number > 65535) continue;
            if (isset($result[$number]) && $result[$number] !== $url) throw new \RuntimeException('Flere ulike PDF-lenker for samme utgavenummer.');
            $result[$number] = $url;
        }
        ksort($result);
        return $result;
    }

    private function publish(int $number, string $url): void
    {
        if (FangenyttIssue::where('number', $number)->where('status', 'published')->exists()) return;
        $disk = Storage::disk(FangenyttArchive::DISK);
        $pdf = 'fangenytt-'.$number.'.pdf';
        $cover = 'covers/fangenytt-'.$number.'.jpg';
        if ($disk->exists($pdf) || $disk->exists($cover)) throw new \RuntimeException('Lokale filer finnes allerede for nr. '.$number.'.');
        $temp = tempnam($disk->path('.'), '.download-');
        if ($temp === false) throw new \RuntimeException('Kunne ikke opprette midlertidig fil.');
        try {
            $response = Http::accept('application/pdf')->connectTimeout(5)->timeout(90)->withOptions([
                'sink' => $temp, 'allow_redirects' => false,
                'progress' => function ($total, $downloaded) { if ($total > self::MAX_SIZE || $downloaded > self::MAX_SIZE) throw new \RuntimeException('PDF-en er for stor.'); },
            ])->get($url);
            // HTTP-fakes may return a body without writing the configured sink.
            if (app()->environment('testing') && filesize($temp) === 0 && $response->body() !== '') file_put_contents($temp, $response->body());
            clearstatcache(true, $temp);
            $size = filesize($temp);
            if (! $response->successful() || ! $size || $size > self::MAX_SIZE || ($response->header('Content-Length') && (int) $response->header('Content-Length') !== $size)) throw new \RuntimeException('PDF-nedlastingen er ufullstendig eller for stor.');
            $head = file_get_contents($temp, false, null, 0, 8);
            $tail = file_get_contents($temp, false, null, max(0, $size - 2048));
            if (strpos($head, '%PDF-') !== 0 || ! preg_match('/%%EOF\s*$/', $tail)) throw new \RuntimeException('Nedlastingen er ikke en komplett PDF.');
            $info = new Process(['pdfinfo', $temp]);
            $info->setTimeout(30);
            $info->run();
            $text = new Process(['pdftotext', '-f', '1', '-l', '1', '-layout', $temp, '-']);
            $text->setTimeout(30);
            $text->run();
            if (! $info->isSuccessful() || ! preg_match('/^Pages:\s+[1-9]\d*/m', $info->getOutput()) || ! $text->isSuccessful() || ! preg_match('/\b(?:nr\.?|nummer)\s*'.preg_quote((string) $number, '/').'(?!\d)/iu', $text->getOutput())) throw new \RuntimeException('PDF-en er ugyldig eller utgavenummeret kunne ikke bekreftes.');
            if (! rename($temp, $disk->path($pdf))) throw new \RuntimeException('Kunne ikke lagre PDF-en.');
            try {
                $result = app(FangenyttCoverGenerator::class)->generate($pdf, $cover);
                if (! $result['success']) throw new \RuntimeException('Kunne ikke generere forside.');
                FangenyttIssue::updateOrCreate(['number' => $number], ['original_url' => $url, 'local_file' => $pdf, 'cover_file' => $cover, 'source' => 'website', 'status' => 'published', 'published_locally_at' => now()]);
            } catch (Throwable $e) {
                $disk->delete([$pdf, $cover]);
                throw $e;
            }
        } finally {
            if (is_file($temp)) unlink($temp);
        }
    }
}
