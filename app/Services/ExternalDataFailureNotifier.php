<?php

namespace App\Services;

use App\Mail\ExternalDataSourceFailureMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ExternalDataFailureNotifier
{
    public function report(string $service, string $summary, array $context = []): void
    {
        try {
            $details = $this->details($service, $summary, $context);

            Log::warning('External data source failure.', [
                'service' => $details['service'],
                'operation' => $details['operation'],
                'failure_kind' => $details['failure_kind'],
                'status' => $details['status'],
                'summary' => $details['summary'],
            ]);

            $recipient = config('feedback.notification_email');
            if (! is_string($recipient) || trim($recipient) === '') {
                return;
            }

            if (! Cache::add($this->cooldownKey($details), true, now()->addSeconds($details['cooldown_seconds']))) {
                return;
            }

            try {
                Mail::to($recipient)->send(new ExternalDataSourceFailureMail($details));
            } catch (Throwable $exception) {
                Log::warning('External data failure notification email could not be sent.', [
                    'service' => $details['service'],
                    'operation' => $details['operation'],
                    'exception' => get_class($exception),
                ]);
            }
        } catch (Throwable $exception) {
            // A notifier failure must never affect the request that observed the source failure.
            try {
                Log::warning('External data failure notifier could not complete.', [
                    'exception' => get_class($exception),
                ]);
            } catch (Throwable $ignored) {
                // Logging infrastructure may itself be unavailable.
            }
        }
    }

    private function details(string $service, string $summary, array $context): array
    {
        $service = $this->identifier($service, 'unknown-service');
        $operation = $this->identifier((string) ($context['operation'] ?? 'default'), 'default');
        $failureKind = $this->identifier((string) ($context['failure_kind'] ?? 'unknown'), 'unknown');
        $status = filter_var($context['status'] ?? null, FILTER_VALIDATE_INT);

        return [
            'service' => $service,
            'operation' => $operation,
            'failure_kind' => $failureKind,
            'status' => $status === false ? null : $status,
            'summary' => $this->sanitizeText($summary),
            'occurred_at' => now('Europe/Oslo')->format('Y-m-d H:i:s T'),
            'cooldown_seconds' => max(1, (int) config('external_data.cooldown_seconds', 3600)),
        ];
    }

    private function cooldownKey(array $details): string
    {
        return 'external-data-failure-notified:'.sha1($details['service'].'|'.$details['operation']);
    }

    private function identifier(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? substr($value, 0, 100) : $fallback;
    }

    private function sanitizeText(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';
        $value = preg_replace('#https?://[^\s]+#i', '[URL skjult]', $value) ?? $value;
        $value = preg_replace('/\bBearer\s+[^\s,;]+/i', 'Bearer [skjult]', $value) ?? $value;
        $value = preg_replace('/\b(authorization|api[-_ ]?key|api[-_ ]?token|token|secret|password|cookie)\s*(?:=|:)\s*[^\s,;]+/i', '$1=[skjult]', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? mb_substr($value, 0, 500) : 'Ingen ytterligere feilmelding tilgjengelig.';
    }
}
