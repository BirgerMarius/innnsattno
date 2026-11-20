<?php

namespace App\Console\Commands;

use App\Mail\ServerMaintenanceStatusMail;
use App\ServerMaintenanceMailDelivery;
use App\ServerMaintenanceStatus;
use App\Services\ServerMaintenanceStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessServerMaintenanceStatus extends Command
{
    protected $signature = 'server-maintenance:process-status';
    protected $description = 'Leser den root-publiserte APT-statusen og sender dedupliserte driftsvarsler';

    public function handle(ServerMaintenanceStatusService $reader): int
    {
        $lock = Cache::lock('server-maintenance:process-status', 300);
        if (! $lock->get()) return self::SUCCESS;
        try {
            $input = $reader->readFile();
            $previous = ServerMaintenanceStatus::latest('id')->first();
            $row = $previous ?: new ServerMaintenanceStatus();
            $wasError = $previous && $previous->state === 'error';
            if ($input['state'] !== 'ok') {
                $row->forceFill(['state' => $input['state'], 'checked_at' => $input['checked_at'] ?? now(), 'last_error' => $input['error'], 'error_alert_sent' => $wasError ? $row->error_alert_sent : false])->save();
                if (! $wasError) $this->queue('error:'.$row->checked_at->format('YmdHis'), 'error', ['error' => $input['error'], 'last_success_at' => optional($row->last_success_at)->timezone('Europe/Oslo')->format('d.m.Y H:i')]);
            } else {
                $changed = ! $previous || $previous->fingerprint !== $input['fingerprint'];
                $hadSentError = $wasError && $previous->error_alert_sent;
                $row->forceFill(['state' => 'ok', 'checked_at' => $input['checked_at'], 'last_success_at' => $input['checked_at'], 'packages' => $input['packages'], 'reboot_required' => $input['reboot_required'], 'fingerprint' => $input['fingerprint'], 'last_error' => null, 'error_alert_sent' => false])->save();
                $payload = ['packages' => $input['packages'], 'last_success_at' => $input['checked_at']->timezone('Europe/Oslo')->format('d.m.Y H:i'), 'reboot_required' => $input['reboot_required'], 'fingerprint' => $input['fingerprint']];
                if ($hadSentError) $this->queue('recovered:'.$input['checked_at']->format('YmdHis'), 'recovered', $payload);
                if ($input['packages'] !== []) {
                    $last = ServerMaintenanceMailDelivery::whereIn('kind', ['update', 'reminder'])->whereNotNull('sent_at')->latest('sent_at')->first();
                    // A second change on the same day is retained in status; it
                    // becomes a new daily notification on the following day.
                    if ($changed || ! $last || (($last->payload['fingerprint'] ?? null) !== $input['fingerprint'])) $this->queue('update:'.now('Europe/Oslo')->toDateString(), 'update', $payload);
                    elseif (! $last || $last->sent_at->lt(now()->subDays(7))) $this->queue('reminder:'.now('Europe/Oslo')->toDateString(), 'reminder', $payload);
                }
            }
            $this->deliver();
            return self::SUCCESS;
        } finally { optional($lock)->release(); }
    }

    private function queue(string $key, string $kind, array $payload): void
    {
        ServerMaintenanceMailDelivery::firstOrCreate(['delivery_key' => $key], ['kind' => $kind, 'payload' => $payload]);
    }

    private function deliver(): void
    {
        if (! config('server_maintenance.enabled')) return;
        $recipient = config('server_maintenance.recipient');
        if (! is_string($recipient) || trim($recipient) === '') { Log::warning('Server maintenance email has no configured recipient.'); return; }
        foreach (ServerMaintenanceMailDelivery::whereNull('sent_at')->orderBy('id')->get() as $delivery) {
            $delivery->increment('attempts', 1, ['last_attempt_at' => now(), 'last_error' => null]);
            try {
                Mail::to($recipient)->send(new ServerMaintenanceStatusMail($delivery->kind, $delivery->payload));
                $delivery->forceFill(['sent_at' => now(), 'last_error' => null])->save();
                if ($delivery->kind === 'error') ServerMaintenanceStatus::latest('id')->first()?->update(['error_alert_sent' => true]);
            } catch (\Throwable $exception) {
                $delivery->forceFill(['last_error' => mb_substr($exception->getMessage(), 0, 2000)])->save();
                Log::warning('Kunne ikke sende varsel om servervedlikehold.', ['delivery' => $delivery->id, 'exception' => get_class($exception)]);
            }
        }
    }
}
