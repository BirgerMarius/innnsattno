<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExternalDataFailureDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $groups, public string $periodStart, public string $periodEnd)
    {
    }

    public function build()
    {
        return $this->subject('innsatt.no: Samlet oversikt over eksterne datakildefeil')
            ->view('emails.external-data-failure-digest');
    }
}
