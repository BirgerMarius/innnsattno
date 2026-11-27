<?php

namespace Tests\Feature;

use App\FeedbackSubmission;
use App\Mail\NewFeedbackSubmissionNotification;
use App\Services\FeedbackProtection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeedbackProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        config()->set('feedback.notification_email', 'test@example.test');
        config()->set('feedback.activity_room_notification_email', 'test@example.test');
        $this->startSession();
        request()->setLaravelSession($this->app['session.store']);
        $this->withCookie(config('session.cookie'), $this->app['session.store']->getId())->withCredentials();
    }

    private function token(string $form = 'feedback', ?string $prison = null): string
    {
        return app(FeedbackProtection::class)->token(request(), $form, $prison);
    }

    private function payload(): array
    {
        return ['type' => 'other', 'title' => 'Hello', 'message' => 'Voir https://example.test – takk!', 'submission_type' => 'anonymous'];
    }

    public function test_forms_render_tokens(): void
    {
        $this->get(route('feedback.create'))->assertOk()->assertSee('name="form_token"', false);
        $this->get(route('prison.activity-rooms', 'ringerike'))->assertOk()->assertSee('name="form_token"', false)->assertSee('data.form_token', false);
    }

    public function test_invalid_missing_expired_and_wrong_scope_tokens_preserve_input_and_allow_retry(): void
    {
        $expired = $this->token();
        $this->travel(25)->hours();
        foreach ([null, 'invalid', $expired, $this->token('activity-room', 'ringerike')] as $token) {
            $this->from(route('feedback.create'))->post(route('feedback.store'), $this->payload() + ['form_token' => $token])
                ->assertSessionHasErrors('form_token')->assertSessionHasInput('message', $this->payload()['message']);
        }
        $this->post(route('feedback.store'), $this->payload() + ['form_token' => $this->token()])->assertSessionHasNoErrors();
        $this->assertSame(1, FeedbackSubmission::count());
    }

    public function test_activity_invalid_tokens_return_replacement_and_retry_works(): void
    {
        $expired = $this->token('activity-room', 'ringerike');
        $this->travel(25)->hours();
        foreach ([null, 'invalid', $expired, $this->token('activity-room', 'ilseng'), $this->token()] as $token) {
            $response = $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), ['message' => 'Bonjour https://example.test', 'form_token' => $token]);
            $response->assertUnprocessable()->assertJsonValidationErrors('form_token')->assertJsonStructure(['form_token']);
        }
        $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), ['message' => 'Bonjour https://example.test', 'form_token' => $response->json('form_token')])->assertCreated();
        $this->assertSame(1, FeedbackSubmission::count());
    }

    public function test_duplicates_are_suppressed_but_other_sessions_forms_and_prisons_are_independent(): void
    {
        $payload = $this->payload() + ['form_token' => $this->token()];
        $this->post(route('feedback.store'), $payload)->assertSessionHas('success');
        $this->post(route('feedback.store'), $payload)->assertSessionHas('success');
        $activity = ['message' => $this->payload()['message'], 'form_token' => $this->token('activity-room', 'ringerike')];
        $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), $activity)->assertCreated();
        $this->postJson(route('prison.activity-rooms.feedback.store', 'ringerike'), $activity)->assertCreated();
        $activity['form_token'] = $this->token('activity-room', 'ilseng');
        $this->postJson(route('prison.activity-rooms.feedback.store', 'ilseng'), $activity)->assertCreated();
        $this->app['session']->regenerate();
        $this->withCookie(config('session.cookie'), $this->app['session.store']->getId());
        $this->post(route('feedback.store'), $payload)->assertSessionHasErrors('form_token');
        $payload['form_token'] = $this->token();
        $this->post(route('feedback.store'), $payload)->assertSessionHas('success');
        $this->assertSame(4, FeedbackSubmission::count());
        Mail::assertSent(NewFeedbackSubmissionNotification::class, 4);
        $this->travel(301)->seconds();
        $this->post(route('feedback.store'), $payload)->assertSessionHas('success');
        $this->assertSame(5, FeedbackSubmission::count());
    }

    public function test_main_form_prison_context_is_bound_and_duplicate_scoped(): void
    {
        $ringerike = $this->token('feedback', 'ringerike');
        $ilseng = $this->token('feedback', 'ilseng');
        $this->post(route('feedback.store', ['prison' => 'ringerike']), $this->payload() + ['form_token' => $ilseng])->assertSessionHasErrors('form_token');
        $this->post(route('feedback.store', ['prison' => 'ringerike']), $this->payload() + ['form_token' => $ringerike])->assertSessionHas('success');
        $this->post(route('feedback.store', ['prison' => 'ilseng']), $this->payload() + ['form_token' => $ilseng])->assertSessionHas('success');
        $this->assertSame(2, FeedbackSubmission::count());
    }

    public function test_failed_storage_can_be_retried(): void
    {
        $payload = $this->payload() + ['form_token' => $this->token()];
        FeedbackSubmission::creating(function () { throw new \RuntimeException('test'); });
        try {
            $this->withoutExceptionHandling()->post(route('feedback.store'), $payload);
            $this->fail('Expected storage failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('test', $exception->getMessage());
        } finally {
            FeedbackSubmission::flushEventListeners();
        }
        $this->post(route('feedback.store'), $payload)->assertSessionHas('success');
        $this->assertSame(1, FeedbackSubmission::count());
    }

    public function test_atomic_lock_excludes_concurrent_work(): void
    {
        $protection = app(FeedbackProtection::class);
        $count = 0;
        $protection->once(request(), 'feedback', null, ['message' => 'same'], function () use ($protection, &$count) {
            $count++;
            try {
                $protection->once(request(), 'feedback', null, ['message' => 'same'], function () use (&$count) { $count++; });
                $this->fail('Concurrent operation should not acquire the lock');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertArrayHasKey('form_token', $exception->errors());
            }
        });
        $protection->once(request(), 'feedback', null, ['message' => 'same'], function () use (&$count) { $count++; });
        $this->assertSame(1, $count);
    }
}
