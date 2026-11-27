<?php

namespace Tests\Feature;

use App\FeedbackSubmission;
use App\Mail\NewFeedbackSubmissionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityRoomFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_room_feedback_is_stored_with_prison_context_and_sent_to_configured_recipient(): void
    {
        Mail::fake();
        config()->set('feedback.activity_room_notification_email', 'innsatt@innsatt.no');

        $response = $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), [
            'form_token' => $this->token(),
            'message' => 'Starttiden bør være tydeligere.',
            'name' => 'Ola Nordmann',
            'email' => 'ola@example.test',
        ]);

        $response->assertCreated()->assertJsonPath('message', 'Takk for tilbakemeldingen. Den er sendt.');
        $this->assertDatabaseHas('feedback_submissions', [
            'type' => 'improvement',
            'title' => 'Tilbakemelding fra aktivitetsrom',
            'name' => 'Ola Nordmann',
            'email' => 'ola@example.test',
        ]);
        $feedback = FeedbackSubmission::firstOrFail();
        $this->assertStringContainsString('/ringerike/aktivitetsrom', $feedback->message);
        $this->assertStringContainsString('Ringerike fengsel (ringerike)', $feedback->message);
        Mail::assertSent(NewFeedbackSubmissionNotification::class, function ($mail) {
            return $mail->hasTo('innsatt@innsatt.no');
        });
    }

    public function test_activity_room_feedback_returns_validation_errors_without_storing_a_submission(): void
    {
        $response = $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), [
            'form_token' => $this->token(),
            'message' => '',
            'email' => 'ikke-en-epost',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['message', 'email']);
        $this->assertSame(0, FeedbackSubmission::count());
    }
    private function token(): string
    {
        $this->startSession();
        request()->setLaravelSession($this->app['session.store']);
        $this->withCookie(config('session.cookie'), $this->app['session.store']->getId())->withCredentials();
        return app(\App\Services\FeedbackProtection::class)->token(request(), 'activity-room', 'ringerike');
    }

    public function test_honeypot_blocks_activity_feedback_and_email(): void
    {
        Mail::fake();
        $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), ['message' => 'Spam', 'website' => 'spam', 'form_token' => $this->token()])->assertCreated();
        $this->assertSame(0, FeedbackSubmission::count());
        Mail::assertNothingSent();
    }
}
