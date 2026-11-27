<?php

namespace App\Http\Controllers;

use App\FeedbackSubmission;
use App\Http\Requests\StoreFeedbackSubmissionRequest;
use App\Mail\NewFeedbackSubmissionNotification;
use App\Services\FeedbackProtection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Throwable;

class FeedbackSubmissionController extends Controller
{
    public function create()
    {
        $prison = $this->feedbackPrison(request());

        return view('feedback.create', [
            'feedbackTypes' => StoreFeedbackSubmissionRequest::TYPES,
            'formToken' => app(FeedbackProtection::class)->token(request(), 'feedback', $prison),
            'feedbackPrison' => $prison,
        ]);
    }

    public function store(StoreFeedbackSubmissionRequest $request)
    {
        if ($request->input('website') !== null && $request->input('website') !== '') {
            return redirect()->route('feedback.create')->with('success', 'Tusen takk for innspillet!');
        }

        $protection = app(FeedbackProtection::class);
        $prison = $this->feedbackPrison($request);
        $protection->validateToken($request, 'feedback', $prison);
        $validated = $request->validated();

        $feedbackSubmission = $protection->once($request, 'feedback', $prison, $validated, function () use ($validated) {
            return FeedbackSubmission::create([
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'is_anonymous' => $validated['submission_type'] === 'anonymous',
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
            ]);
        });

        if ($feedbackSubmission) {
            $this->sendNotification($feedbackSubmission);
        }

        return redirect()
            ->route('feedback.create')
            ->with('success', 'Tusen takk for innspillet! Jeg setter stor pris på alle forslag og tilbakemeldinger. Forslaget ditt er mottatt og vil bli vurdert.');
    }

    public function storeActivityRoom(Request $request, string $prison)
    {
        if ($request->input('website') !== null && $request->input('website') !== '') {
            return response()->json(['message' => 'Takk for tilbakemeldingen. Den er sendt.'], 201);
        }

        $protection = app(FeedbackProtection::class);
        try {
            $protection->validateToken($request, 'activity-room', $prison);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
                'form_token' => $protection->token($request, 'activity-room', $prison),
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'message.required' => 'Skriv en tilbakemelding før du sender.',
            'message.max' => 'Tilbakemeldingen kan ikke være lengre enn 5000 tegn.',
            'name.max' => 'Navnet kan ikke være lengre enn 150 tegn.',
            'email.email' => 'Skriv en gyldig e-postadresse.',
            'email.max' => 'E-postadressen kan ikke være lengre enn 255 tegn.',
        ]);

        $prisonName = config('prisons.all.'.$prison.'.name', $prison);
        $pageUrl = route('prison.activity-rooms', ['prison' => $prison]);

        $feedbackSubmission = $protection->once($request, 'activity-room', $prison, $validated, function () use ($validated, $pageUrl, $prisonName, $prison) {
            return FeedbackSubmission::create([
                'type' => 'improvement',
                'title' => 'Tilbakemelding fra aktivitetsrom',
                'message' => trim($validated['message'])."\n\nSide: {$pageUrl}\nFengsel: {$prisonName} ({$prison})",
                'is_anonymous' => empty($validated['name']) && empty($validated['email']),
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
            ]);
        });

        if ($feedbackSubmission) {
            $this->sendNotification($feedbackSubmission, config('feedback.activity_room_notification_email'));
        }

        return response()->json([
            'message' => 'Takk for tilbakemeldingen. Den er sendt.',
        ], 201);
    }

    private function feedbackPrison(Request $request): ?string
    {
        $prison = $request->query('prison');

        return is_string($prison) && array_key_exists($prison, config('prisons.all')) ? $prison : null;
    }

    private function sendNotification(FeedbackSubmission $feedbackSubmission, ?string $recipient = null): void
    {
        $recipient = $recipient ?? config('feedback.notification_email');

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
