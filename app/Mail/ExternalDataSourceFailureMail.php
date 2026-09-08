<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExternalDataSourceFailureMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $service;
    public string $operation;
    public string $summary;
    public string $failureKind;
    public ?int $status;
    public string $occurredAt;
    public int $cooldownSeconds;
    public array $diagnostics;

    public function __construct(array $details)
    {
        $this->service = $details['service'];
        $this->operation = $details['operation'];
        $this->summary = $details['summary'];
        $this->failureKind = $details['failure_kind'];
        $this->status = $details['status'];
        $this->occurredAt = $details['occurred_at'];
        $this->cooldownSeconds = $details['cooldown_seconds'];
        $this->diagnostics = $details['diagnostics'];
    }

    public function build()
    {
        return $this->subject('innsatt.no: Feil ved ekstern datakilde – '.$this->service)
            ->view('emails.external-data-source-failure');
    }
}
