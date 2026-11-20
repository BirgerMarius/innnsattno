<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServerMaintenanceStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $kind, public array $payload)
    {
    }

    public function build()
    {
        $subject = match ($this->kind) {
            'error' => 'Innsatt.no – kontroll av serveroppdateringer feilet',
            'recovered' => 'Innsatt.no – kontroll av serveroppdateringer er friskmeldt',
            'reminder' => 'Innsatt.no – påminnelse om ventende serveroppdateringer',
            default => 'Innsatt.no – tilgjengelige serveroppdateringer',
        };

        return $this->subject($subject)->view('emails.server-maintenance-status')->text('emails.server-maintenance-status-text');
    }
}
