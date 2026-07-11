<?php

namespace App\Http\Controllers;

use App\FeedbackSubmission;
use App\Http\Requests\StoreFeedbackSubmissionRequest;
use App\Mail\NewFeedbackSubmissionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackSubmissionController extends Controller
{
    public function create()
    {
        return view('feedback.create', [
            'feedbackTypes' => StoreFeedbackSubmissionRequest::TYPES,
        ]);
    }

    public function store(StoreFeedbackSubmissionRequest $request)
    {
        $validated = $request->validated();

        $feedbackSubmission = FeedbackSubmission::create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_anonymous' => $validated['submission_type'] === 'anonymous',
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        $this->sendNotification($feedbackSubmission);

        return redirect()
            ->route('feedback.create')
            ->with('success', 'Tusen takk for innspillet! Jeg setter stor pris på alle forslag og tilbakemeldinger. Forslaget ditt er mottatt og vil bli vurdert.');
    }

    private function sendNotification(FeedbackSubmission $feedbackSubmission): void
    {
        $recipient = config('feedback.notification_email');

        if (! $recipient) {
            return;
        }

        try {
            Mail::to($recipient)->send(new NewFeedbackSubmissionNotification($feedbackSubmission));
        } catch (Throwable $exception) {
            Log::warning('Feedback notification email could not be sent.', [
                'feedback_submission_id' => $feedbackSubmission->id,
                'exception' => get_class($exception),
            ]);
        }
    }
}
