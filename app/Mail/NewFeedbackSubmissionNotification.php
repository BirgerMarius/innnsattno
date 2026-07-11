<?php

namespace App\Mail;

use App\FeedbackSubmission;
use App\Http\Requests\StoreFeedbackSubmissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewFeedbackSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public FeedbackSubmission $feedbackSubmission;

    public string $typeLabel;

    public string $statusLabel;

    public function __construct(FeedbackSubmission $feedbackSubmission)
    {
        $this->feedbackSubmission = $feedbackSubmission;
        $this->typeLabel = StoreFeedbackSubmissionRequest::TYPES[$feedbackSubmission->type] ?? 'Ukjent';
        $this->statusLabel = $feedbackSubmission->status === 'new' ? 'Ny' : ucfirst($feedbackSubmission->status);
    }

    public function build()
    {
        return $this->subject('Nytt forslag mottatt på Innsatt.no')
            ->view('emails.feedback.new-submission');
    }
}
