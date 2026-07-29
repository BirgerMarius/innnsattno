<?php

namespace Tests\Feature;

use App\FeedbackSubmission;
use App\Mail\NewFeedbackSubmissionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FeedbackSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function testFeedbackPageCanBeOpened()
    {
        $response = $this->get('/forslag-og-tilbakemeldinger');

        $response->assertStatus(200);
        $response->assertSee('Forslag og tilbakemeldinger');
    }

    public function testValidAnonymousFeedbackCanBeStored()
    {
        Mail::fake();

        $response = $this->post('/forslag-og-tilbakemeldinger', $this->validAnonymousPayload());

        $response->assertRedirect(route('feedback.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('feedback_submissions', [
            'type' => 'new_idea',
            'title' => 'Legg til mer informasjon',
            'is_anonymous' => true,
            'status' => 'new',
        ]);
    }

    public function testFeedbackNotificationEmailIsSentToConfiguredAddress()
    {
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');

        $this->post('/forslag-og-tilbakemeldinger', $this->validAnonymousPayload());

        Mail::assertSent(NewFeedbackSubmissionNotification::class, 1);

        $sentMail = Mail::sent(NewFeedbackSubmissionNotification::class)->first();
        $this->assertTrue($sentMail->hasTo('varsling@example.test'));
        $this->assertSame('Legg til mer informasjon', $sentMail->feedbackSubmission->title);
        $this->assertSame(
            'Det hadde vaert nyttig med en oversikt over flere tjenester.',
            $sentMail->feedbackSubmission->message
        );
        $this->assertSame('Ny idé', $sentMail->typeLabel);

        $mail = new NewFeedbackSubmissionNotification(FeedbackSubmission::firstOrFail());
        $mail->build();
        $rendered = $this->renderFeedbackMail($mail);

        $this->assertTrue($mail->hasSubject('Nytt forslag mottatt på Innsatt.no'));
        $this->assertStringContainsString('Legg til mer informasjon', $rendered);
        $this->assertStringContainsString(
            'Det hadde vaert nyttig med en oversikt over flere tjenester.',
            $rendered
        );
    }

    public function testAnonymousFeedbackNotificationDoesNotIncludeContactDetails()
    {
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');

        $this->post('/forslag-og-tilbakemeldinger', $this->validAnonymousPayload());

        Mail::assertSent(NewFeedbackSubmissionNotification::class, function ($mail) {
            $rendered = $this->renderFeedbackMail($mail);

            return $mail->feedbackSubmission->is_anonymous
                && str_contains($rendered, 'Anonym innsending')
                && str_contains($rendered, 'Ja')
                && ! str_contains($rendered, 'Navn')
                && ! str_contains($rendered, 'E-post')
                && ! str_contains($rendered, 'ola@example.test');
        });
    }

    public function testContactFeedbackNotificationIncludesNameAndEmail()
    {
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');

        $this->post('/forslag-og-tilbakemeldinger', [
            'type' => 'bug',
            'title' => 'Feil lenke',
            'message' => 'En lenke virker ikke.',
            'submission_type' => 'contact',
            'name' => 'Ola Nordmann',
            'email' => 'ola@example.test',
        ]);

        Mail::assertSent(NewFeedbackSubmissionNotification::class, function ($mail) {
            $rendered = $this->renderFeedbackMail($mail);

            return ! $mail->feedbackSubmission->is_anonymous
                && str_contains($rendered, 'Feil på nettsiden')
                && str_contains($rendered, 'Ola Nordmann')
                && str_contains($rendered, 'ola@example.test');
        });
    }

    public function testNotificationEmailIsNotAttemptedWhenRecipientIsMissing()
    {
        Mail::fake();
        config()->set('feedback.notification_email', null);

        $this->post('/forslag-og-tilbakemeldinger', $this->validAnonymousPayload());

        Mail::assertNothingSent();
    }

    public function testFeedbackIsStoredWhenNotificationEmailFails()
    {
        config()->set('feedback.notification_email', 'varsling@example.test');

        Mail::shouldReceive('to')
            ->once()
            ->with('varsling@example.test')
            ->andThrow(new RuntimeException('Mail transport failed'));

        Log::shouldReceive('warning')
            ->once()
            ->with('Feedback notification email could not be sent.', Mockery::on(function ($context) {
                return isset($context['feedback_submission_id'])
                    && $context['exception'] === RuntimeException::class
                    && ! array_key_exists('message', $context)
                    && ! array_key_exists('email', $context)
                    && ! array_key_exists('name', $context);
            }));

        $response = $this->post('/forslag-og-tilbakemeldinger', $this->validAnonymousPayload());

        $response->assertRedirect(route('feedback.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('feedback_submissions', [
            'type' => 'new_idea',
            'title' => 'Legg til mer informasjon',
            'status' => 'new',
        ]);
    }

    public function testContactInformationIsRequiredWhenSenderCanBeContacted()
    {
        $response = $this->from('/forslag-og-tilbakemeldinger')
            ->post('/forslag-og-tilbakemeldinger', [
                'type' => 'improvement',
                'title' => 'Bedre utskrift',
                'message' => 'Utskriftssiden kan gjores tydeligere.',
                'submission_type' => 'contact',
            ]);

        $response->assertRedirect('/forslag-og-tilbakemeldinger');
        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertSame(0, FeedbackSubmission::count());
    }

    public function testInvalidEmailIsRejected()
    {
        $response = $this->from('/forslag-og-tilbakemeldinger')
            ->post('/forslag-og-tilbakemeldinger', [
                'type' => 'bug',
                'title' => 'Feil lenke',
                'message' => 'En lenke virker ikke.',
                'submission_type' => 'contact',
                'name' => 'Ola Nordmann',
                'email' => 'ikke-en-epost',
            ]);

        $response->assertRedirect('/forslag-og-tilbakemeldinger');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame(0, FeedbackSubmission::count());
    }

    public function testRequiredFieldsAreValidated()
    {
        $response = $this->from('/forslag-og-tilbakemeldinger')
            ->post('/forslag-og-tilbakemeldinger', []);

        $response->assertRedirect('/forslag-og-tilbakemeldinger');
        $response->assertSessionHasErrors(['type', 'title', 'message', 'submission_type']);
        $this->assertSame(0, FeedbackSubmission::count());
    }

    private function renderFeedbackMail(NewFeedbackSubmissionNotification $mail): string
    {
        return view('emails.feedback.new-submission', [
            'feedbackSubmission' => $mail->feedbackSubmission,
            'typeLabel' => $mail->typeLabel,
            'statusLabel' => $mail->statusLabel,
        ])->render();
    }

    private function validAnonymousPayload(): array
    {
        return [
            'type' => 'new_idea',
            'title' => 'Legg til mer informasjon',
            'message' => 'Det hadde vaert nyttig med en oversikt over flere tjenester.',
            'submission_type' => 'anonymous',
        ];
    }
}
