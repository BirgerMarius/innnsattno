<?php

namespace Tests\Feature;

use App\FeedbackSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminFeedbackPageRequiresLogin()
    {
        $response = $this->get('/admin/forslag');

        $response->assertRedirect(route('admin.login'));
    }

    public function testAdminCanLoginWithConfiguredCredentials()
    {
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.password_hash', Hash::make('hemmelig-passord'));

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'hemmelig-passord',
        ]);

        $response->assertRedirect(route('admin.professional-resources.index'));
        $response->assertSessionHas('admin_authenticated', true);
    }

    public function testAdminCanLoginWithEmailUsingDifferentLetterCase()
    {
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.password_hash', Hash::make('hemmelig-passord'));

        $response = $this->post('/admin/login', [
            'email' => 'Admin@Example.Test',
            'password' => 'hemmelig-passord',
        ]);

        $response->assertRedirect(route('admin.professional-resources.index'));
        $response->assertSessionHas('admin_authenticated', true);
    }

    public function testAdminCanSeeFeedbackNewestFirst()
    {
        $oldFeedback = FeedbackSubmission::create([
            'type' => 'bug',
            'title' => 'Gammelt forslag',
            'message' => 'Eldre melding.',
            'is_anonymous' => true,
            'status' => 'new',
        ]);
        $oldFeedback->created_at = now()->subDay();
        $oldFeedback->updated_at = now()->subDay();
        $oldFeedback->save();

        $newFeedback = FeedbackSubmission::create([
            'type' => 'new_idea',
            'title' => 'Nytt forslag',
            'message' => 'Nyere melding.',
            'is_anonymous' => false,
            'name' => 'Ola Nordmann',
            'email' => 'ola@example.test',
            'status' => 'in_progress',
        ]);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/forslag');

        $response->assertStatus(200);
        $response->assertSeeInOrder([$newFeedback->title, $oldFeedback->title]);
        $response->assertSee('Under behandling');
        $response->assertSee('Ola Nordmann');
        $response->assertSee('ola@example.test');
    }

    public function testAdminCanFilterFeedbackByStatus()
    {
        $newFeedback = $this->createFeedbackSubmission([
            'title' => 'Nytt innspill',
            'status' => 'new',
        ]);
        $completedFeedback = $this->createFeedbackSubmission([
            'title' => 'Ferdig innspill',
            'status' => 'completed',
        ]);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/forslag?status=completed');

        $response->assertStatus(200);
        $response->assertSee($completedFeedback->title);
        $response->assertDontSee($newFeedback->title);
        $response->assertSee('Ferdig');
    }

    public function testInvalidStatusFilterRedirectsWithoutStatus()
    {
        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/forslag?status=deleted');

        $response->assertRedirect(route('admin.feedback.index'));
        $response->assertSessionHas('warning', 'Ugyldig statusfilter ble fjernet.');
        $this->assertStringNotContainsString('deleted', $response->headers->get('Location'));
    }

    public function testStatusCountsShowTotalsForAllFeedback()
    {
        $this->createFeedbackSubmission(['status' => 'new']);
        $this->createFeedbackSubmission(['status' => 'in_progress']);
        $this->createFeedbackSubmission(['status' => 'archived']);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/forslag?status=new');

        $response->assertStatus(200);
        $response->assertSee('Alle <span class="badge bg-light text-dark">3</span>', false);
        $response->assertSee('Nye <span class="badge bg-light text-dark">1</span>', false);
        $response->assertSee('Under behandling <span class="badge bg-light text-dark">1</span>', false);
        $response->assertSee('Ferdige <span class="badge bg-light text-dark">0</span>', false);
        $response->assertSee('Arkiverte <span class="badge bg-light text-dark">1</span>', false);
    }

    public function testAdminFeedbackFilterShowsEmptyMessage()
    {
        $this->createFeedbackSubmission(['status' => 'new']);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/forslag?status=completed');

        $response->assertStatus(200);
        $response->assertSee('Det finnes ingen forslag eller tilbakemeldinger med statusen ferdig.');
    }

    public function testAdminCanUpdateStatusAndInternalNote()
    {
        $feedback = FeedbackSubmission::create([
            'type' => 'improvement',
            'title' => 'Forbedring',
            'message' => 'Kan bli bedre.',
            'is_anonymous' => true,
            'status' => 'new',
        ]);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->patch(route('admin.feedback.update', $feedback), [
                'status' => 'completed',
                'admin_note' => 'Fulgt opp internt.',
            ]);

        $response->assertRedirect(route('admin.feedback.index'));

        $this->assertDatabaseHas('feedback_submissions', [
            'id' => $feedback->id,
            'status' => 'completed',
            'admin_note' => 'Fulgt opp internt.',
        ]);
    }

    public function testAdminStatusIsValidated()
    {
        $feedback = FeedbackSubmission::create([
            'type' => 'other',
            'title' => 'Annet',
            'message' => 'Melding.',
            'is_anonymous' => true,
            'status' => 'new',
        ]);

        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->from('/admin/forslag')
            ->patch(route('admin.feedback.update', $feedback), [
                'status' => 'deleted',
                'admin_note' => 'Skal ikke lagres.',
            ]);

        $response->assertRedirect('/admin/forslag');
        $response->assertSessionHasErrors(['status']);

        $this->assertDatabaseHas('feedback_submissions', [
            'id' => $feedback->id,
            'status' => 'new',
            'admin_note' => null,
        ]);
    }

    private function createFeedbackSubmission(array $attributes = []): FeedbackSubmission
    {
        return FeedbackSubmission::create(array_merge([
            'type' => 'new_idea',
            'title' => 'Forslag',
            'message' => 'Melding.',
            'is_anonymous' => true,
            'status' => 'new',
        ], $attributes));
    }
}
