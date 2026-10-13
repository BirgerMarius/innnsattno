<?php

namespace App\Services;

use App\ExternalDataSourceEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
                'diagnostics' => $details['diagnostics'],
            ]);

            $this->record(ExternalDataSourceEvent::OUTCOME_FAILURE, $details);
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

    /** Record a completed, validated source refresh so the digest can show recovery. */
    public function reportSuccess(string $service, string $operation = 'default'): void
    {
        try {
            $this->record(ExternalDataSourceEvent::OUTCOME_SUCCESS, [
                'service' => $this->identifier($service, 'unknown-service'),
                'operation' => $this->identifier($operation, 'default'),
            ]);
        } catch (Throwable $exception) {
            Log::warning('External data source success could not be recorded.', ['exception' => get_class($exception)]);
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
            'diagnostics' => $this->diagnostics($context),
            'occurred_at' => now('Europe/Oslo')->format('Y-m-d H:i:s T'),
        ];
    }

    private function record(string $outcome, array $details): void
    {
        // Deployment is safe even if the migration has not yet been run.
        if (! Schema::hasTable('external_data_source_events')) {
            return;
        }
        ExternalDataSourceEvent::create([
            'service' => $details['service'], 'operation' => $details['operation'], 'outcome' => $outcome,
            'failure_kind' => $details['failure_kind'] ?? null, 'status' => $details['status'] ?? null,
            'summary' => $details['summary'] ?? null, 'diagnostics' => $details['diagnostics'] ?? null,
            'occurred_at' => now(),
        ]);
    }

    private function diagnostics(array $context): array
    {
        $diagnostics = [];

        foreach (['location_id', 'year', 'month', 'row_index'] as $key) {
            $value = filter_var($context[$key] ?? null, FILTER_VALIDATE_INT);
            if ($value !== false && $value !== null) {
                $diagnostics[$key] = $value;
            }
        }

        foreach (['date', 'missing_field', 'invalid_field', 'payload_type', 'row_type', 'date_type', 'value_type'] as $key) {
            if (! is_scalar($context[$key] ?? null)) {
                continue;
            }

            $value = $this->sanitizeText((string) $context[$key]);
            if ($value !== '') {
                $diagnostics[$key] = $value;
            }
        }

        return $diagnostics;
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
