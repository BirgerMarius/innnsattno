<?php

namespace Tests\Feature;

use App\Services\DailyWordService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DailyWordManagementTest extends TestCase
{
    use RefreshDatabase;

    private array $originalWords;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalWords = config('daily_word.words');
        $now = CarbonImmutable::parse('2026-01-01 12:00:00', 'Europe/Oslo');
        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);
        Http::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        config(['daily_word.words' => $this->originalWords]);
        parent::tearDown();
    }

    public function testHomepageUsesSeparateSelectionAndPrintLinksForEachPrison(): void
    {
        foreach (array_keys(config('prisons.all')) as $prison) {
            $header = $this->get('/'.$prison)->assertOk()->getContent();
            $word = app(DailyWordService::class)->forDate();

            $this->assertStringContainsString('href="'.url('/'.$prison.'/dagens-ord').'"', $header);
            $this->assertStringContainsString('href="'.url('/'.$prison.'/dagens-ord/utskrift/'.$word['id']).'"', $header);
            $this->assertStringNotContainsString('<a class="daily-word"', $header);
        }
    }

    public function testSelectionPageListsActiveWordsAndKeepsPrisonContextForPrint(): void
    {
        config(['daily_word.words' => [
            ['id' => 'first', 'text' => 'Første tekst.'],
            ['id' => 'second', 'text' => 'Andre tekst.'],
        ]]);

        $this->get('/ilseng/dagens-ord')
            ->assertOk()
            ->assertSee('Første tekst.')
            ->assertSee('Andre tekst.');

        app(DailyWordService::class)->deactivate('second');

        $this->get('/ilseng/dagens-ord')
            ->assertOk()
            ->assertSee('Første tekst.')
            ->assertDontSee('Andre tekst.')
            ->assertSee('href="'.url('/ilseng/dagens-ord/utskrift/first').'?return_to=%2Filseng%2Fdagens-ord"', false)
            ->assertSee('href="/ilseng"', false);

        $this->get('/ilseng/dagens-ord/utskrift/first?return_to=%2Filseng%2Fdagens-ord')
            ->assertOk()
            ->assertSee('Første tekst.')
            ->assertSee('const returnUrl = "/ilseng/dagens-ord";', false);
        $this->get('/ilseng/dagens-ord/utskrift/second')->assertNotFound();
    }

    public function testAdminDeactivationChangesTheSharedRotationAndCanBeReversed(): void
    {
        config(['daily_word.words' => [
            ['id' => 'first', 'text' => 'Første tekst.'],
            ['id' => 'second', 'text' => 'Andre tekst.'],
            ['id' => 'third', 'text' => 'Tredje tekst.'],
        ]]);
        $service = app(DailyWordService::class);

        $this->assertSame('second', $service->forDate(CarbonImmutable::parse('2026-01-02', 'Europe/Oslo'))['id']);

        $this->withSession(['admin_authenticated' => true])
            ->post('/admin/dagens-ord/second/deaktiver')
            ->assertRedirect('/adm');

        $this->assertDatabaseHas('daily_word_statuses', ['word_id' => 'second', 'is_active' => false]);
        $this->assertSame('third', $service->forDate(CarbonImmutable::parse('2026-01-02', 'Europe/Oslo'))['id']);
        $this->get('/ringerike/dagens-ord')->assertDontSee('Andre tekst.');
        $this->withSession(['admin_authenticated' => true])->get('/adm')
            ->assertSee('02.01.2026')
            ->assertSee('Tredje tekst.')
            ->assertSee('Deaktiverte tekster (1)')
            ->assertSee('Aktiver igjen');

        $this->withSession(['admin_authenticated' => true])
            ->post('/admin/dagens-ord/second/aktiver')
            ->assertRedirect('/adm');

        $this->assertDatabaseHas('daily_word_statuses', ['word_id' => 'second', 'is_active' => true]);
        $this->assertSame('second', $service->forDate(CarbonImmutable::parse('2026-01-02', 'Europe/Oslo'))['id']);
    }

    public function testUnauthenticatedRequestsCannotChangeWordStatus(): void
    {
        $this->post('/admin/dagens-ord/rolig-start/deaktiver')
            ->assertRedirect(route('admin.login'));

        $this->assertSame(0, DB::table('daily_word_statuses')->count());
        $this->get('/admin/dagens-ord/rolig-start/deaktiver')->assertStatus(405);
    }

    public function testNoActiveWordsHidesTheHomepageMessageAndShowsEmptyStates(): void
    {
        config(['daily_word.words' => [['id' => 'only', 'text' => 'Bare tekst.']]]);
        app(DailyWordService::class)->deactivate('only');

        $this->assertNull(app(DailyWordService::class)->forDate());
        $this->get('/kongsvinger')->assertOk()->assertDontSee('Dagens ord');
        $this->get('/kongsvinger/dagens-ord')->assertOk()
            ->assertSee('Det er ingen aktive tekster tilgjengelig for utskrift akkurat nå.');
        $this->withSession(['admin_authenticated' => true])->get('/adm')->assertOk()
            ->assertSee('Ingen aktive Dagens ord er tilgjengelig.')
            ->assertSee('Aktiver igjen');
    }
}
