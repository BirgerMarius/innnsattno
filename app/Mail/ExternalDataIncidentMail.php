<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ExternalDataIncidentMail extends Mailable
{
    public function __construct(public string $service, public string $state, public array $coverages)
    {
    }

    public function build()
    {
        $label = $this->state === 'recovered' ? 'Gjenopprettet' : ($this->state === 'reminder' ? 'Påminnelse' : 'Kritisk datakilfeil');
        return $this->subject('innsatt.no: '.$label.' – '.$this->service)->view('emails.external-data-incident');
    }
}
