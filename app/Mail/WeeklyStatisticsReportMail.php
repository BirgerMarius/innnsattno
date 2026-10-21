<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyStatisticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $report)
    {
    }

    public function build()
    {
        return $this->subject($this->report['subject'])
            ->view('emails.weekly-statistics-report')
            ->text('emails.weekly-statistics-report-text');
    }
}
