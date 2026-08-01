<?php

namespace Tests\Feature;

use App\Services\ChangeHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ChangeHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function testHomepageUpdateDateLinksToPublicChangeHistory(): void
    {
        $this->fakeHistory();

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('href="'.route('change-history.index').'"', false)
            ->assertSee('Siden er sist oppdatert: 01.08.2026');

        $this->get('/endringer')->assertOk();
    }

    public function testChangesAreShownNewestFirstAndGroupedWithNorwegianDates(): void
    {
        $this->fakeHistory();

        $response = $this->get('/endringer')
            ->assertOk()
            ->assertSee('Endringer på innsatt.no')
            ->assertSeeInOrder([
                '01.08.2026',
                'Nyeste endring',
                'En annen endring samme dag',
                '31.07.2026',
                'Eldre endring',
            ]);

        $this->assertSame(1, substr_count($response->getContent(), 'id="changes-2026-08-01"'));
    }

    public function testInternalGitDetailsAreNotRendered(): void
    {
        $this->fakeHistory([
            'repository_path' => '/srv/private/innsatt',
            'branch' => 'secret-release-branch',
            'author_email' => 'developer@example.test',
            'commit_id' => '0123456789abcdef',
        ]);

        $this->get('/endringer')
            ->assertOk()
            ->assertDontSee('/srv/private/innsatt')
            ->assertDontSee('secret-release-branch')
            ->assertDontSee('developer@example.test')
            ->assertDontSee('0123456789abcdef');
    }

    public function testMissingGitHistoryShowsFriendlyMessageWithoutServerError(): void
    {
        config(['change-history.repository_path' => base_path('does-not-exist')]);

        $this->get('/endringer')
            ->assertOk()
            ->assertSee('Endringshistorikken er dessverre ikke tilgjengelig akkurat nå');
    }

    private function fakeHistory(array $internalDetails = []): void
    {
        $augustFirst = CarbonImmutable::parse('2026-08-01T12:00:00+02:00')->locale('nb');
        $julyThirtyFirst = CarbonImmutable::parse('2026-07-31T18:00:00+02:00')->locale('nb');
        $history = array_merge([
            'available' => true,
            'updated_at' => $augustFirst,
            'groups' => [
                '2026-08-01' => [
                    ['date' => $augustFirst, 'message' => 'Nyeste endring'],
                    ['date' => $augustFirst->subHour(), 'message' => 'En annen endring samme dag'],
                ],
                '2026-07-31' => [
                    ['date' => $julyThirtyFirst, 'message' => 'Eldre endring'],
                ],
            ],
        ], $internalDetails);

        $service = Mockery::mock(ChangeHistoryService::class);
        $service->shouldReceive('get')->andReturn($history);
        $this->app->instance(ChangeHistoryService::class, $service);
    }
}
