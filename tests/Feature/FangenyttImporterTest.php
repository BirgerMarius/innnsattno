<?php

namespace Tests\Feature;

use App\FangenyttIssue;
use App\Services\FangenyttCoverGenerator;
use App\Services\FangenyttImporter;
use Database\Seeders\FangenyttIssueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FangenyttImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fangenytt');

        $coverGenerator = Mockery::mock(FangenyttCoverGenerator::class);
        $coverGenerator->shouldReceive('generate')->byDefault()->andReturnUsing(function ($pdfFile, $coverFile) {
            Storage::disk('fangenytt')->put($coverFile, 'jpeg');

            return ['success' => true, 'message' => $coverFile];
        });
        $this->app->instance(FangenyttCoverGenerator::class, $coverGenerator);
    }

    public function testHistoricalIssuesCanBeSeededRepeatedlyWithoutDuplicates(): void
    {
        $this->seed(FangenyttIssueSeeder::class);
        $this->seed(FangenyttIssueSeeder::class);

        $this->assertSame(18, FangenyttIssue::count());
        $this->assertDatabaseHas('fangenytt_issues', [
            'number' => 18,
            'local_file' => 'fangenytt-18.pdf',
            'cover_file' => 'covers/fangenytt-18.jpg',
            'status' => FangenyttIssue::STATUS_PUBLISHED,
        ]);
    }

    public function testManualCommandImportsAndPublishesANewIssueWithoutConfigChange(): void
    {
        Http::fake(['https://www.fangeforeningen.no/*' => Http::response('%PDF-1.7 test', 200)]);

        $this->artisan('fangenytt:import', [
            'number' => 19,
            'url' => 'https://www.fangeforeningen.no/wp-content/uploads/fangenytt-19.pdf',
            '--edition' => '1/2027',
            '--source' => 'manual',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('fangenytt_issues', ['number' => 19, 'status' => FangenyttIssue::STATUS_PUBLISHED]);
        Storage::disk('fangenytt')->assertExists('fangenytt-19.pdf');
        Storage::disk('fangenytt')->assertExists('covers/fangenytt-19.jpg');
        $this->get('/fangenytt')->assertOk()->assertSee('Fangenytt nr. 19');
        $this->get('/fangenytt/19/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/fangenytt/19/cover')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function testDuplicateNumberAndUnapprovedSourceAreRejected(): void
    {
        FangenyttIssue::create($this->issue(19));
        Http::fake();

        $this->artisan('fangenytt:import', ['number' => 19, 'url' => 'https://www.fangeforeningen.no/fangenytt-19.pdf'])
            ->assertExitCode(1);
        $this->artisan('fangenytt:import', ['number' => 20, 'url' => 'https://example.test/fangenytt-20.pdf'])
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function testNonPdfAndHttpFailuresAreNotPublishedAndCleanUpFiles(): void
    {
        Http::fake(['https://www.fangeforeningen.no/not-pdf.pdf' => Http::response('<html>nei</html>', 200)]);

        $result = app(FangenyttImporter::class)->import([
            'number' => 20,
            'original_url' => 'https://www.fangeforeningen.no/not-pdf.pdf',
            'source' => 'website',
        ]);

        $this->assertFalse($result['success']);
        $this->assertDatabaseHas('fangenytt_issues', ['number' => 20, 'status' => FangenyttIssue::STATUS_FAILED]);
        Storage::disk('fangenytt')->assertMissing('fangenytt-20.pdf');
        $this->get('/fangenytt')->assertOk()->assertDontSee('Fangenytt nr. 20');
        $this->get('/fangenytt/20/pdf')->assertNotFound();

        Http::fake(['https://www.fangeforeningen.no/http-failure.pdf' => Http::response('', 502)]);
        $result = app(FangenyttImporter::class)->import([
            'number' => 21,
            'original_url' => 'https://www.fangeforeningen.no/http-failure.pdf',
            'source' => 'website',
        ]);
        $this->assertFalse($result['success']);
        $this->assertDatabaseHas('fangenytt_issues', ['number' => 21, 'status' => FangenyttIssue::STATUS_FAILED]);
    }

    public function testPendingAndFailedIssuesAreHiddenFromThePublicArchive(): void
    {
        FangenyttIssue::create($this->issue(20, FangenyttIssue::STATUS_PENDING));
        FangenyttIssue::create($this->issue(21, FangenyttIssue::STATUS_FAILED));
        FangenyttIssue::create($this->issue(22, FangenyttIssue::STATUS_PUBLISHED));

        $this->get('/fangenytt')
            ->assertOk()
            ->assertSee('Fangenytt nr. 22')
            ->assertDontSee('Fangenytt nr. 20')
            ->assertDontSee('Fangenytt nr. 21');
    }

    private function issue(int $number, string $status = FangenyttIssue::STATUS_PUBLISHED): array
    {
        return [
            'number' => $number,
            'original_url' => 'https://www.fangeforeningen.no/fangenytt-'.$number.'.pdf',
            'local_file' => 'fangenytt-'.$number.'.pdf',
            'cover_file' => 'covers/fangenytt-'.$number.'.jpg',
            'source' => 'test',
            'status' => $status,
        ];
    }
}
