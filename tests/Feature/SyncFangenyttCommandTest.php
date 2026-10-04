<?php

namespace Tests\Feature;

use App\Services\FangenyttCoverGenerator;
use App\FangenyttIssue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SyncFangenyttCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('fangenytt');
        FangenyttIssue::create([
            'number' => 18,
            'original_url' => 'https://fangeforeningen.test/fangenytt-18.pdf',
            'local_file' => 'fangenytt-18.pdf',
            'cover_file' => 'covers/fangenytt-18.jpg',
            'source' => 'test',
            'status' => FangenyttIssue::STATUS_PUBLISHED,
        ]);

        $coverGenerator = Mockery::mock(FangenyttCoverGenerator::class);
        $coverGenerator->shouldReceive('generate')
            ->byDefault()
            ->andReturn(['success' => true, 'message' => 'covers/fangenytt-18.jpg']);
        $this->app->instance(FangenyttCoverGenerator::class, $coverGenerator);
    }

    public function testCommandDownloadsSimulatedPdf(): void
    {
        Http::fake(['https://fangeforeningen.test/*' => Http::response('%PDF-1.7 simulated PDF', 200)]);

        $this->artisan('fangenytt:sync')
            ->expectsOutputToContain('lastet ned')
            ->expectsOutputToContain('forside generert')
            ->assertExitCode(0);

        Storage::disk('fangenytt')->assertExists('fangenytt-18.pdf');
    }

    public function testCommandReportsInvalidHttpResponseAndContinues(): void
    {
        Http::fake(['https://fangeforeningen.test/*' => Http::response('Ikke funnet', 404)]);

        $this->artisan('fangenytt:sync')
            ->expectsOutputToContain('HTTP-status 404')
            ->assertExitCode(1);

        Storage::disk('fangenytt')->assertMissing('fangenytt-18.pdf');
    }

    public function testCommandRejectsNonPdfResponse(): void
    {
        Http::fake(['https://fangeforeningen.test/*' => Http::response('<html>feil</html>', 200)]);

        $this->artisan('fangenytt:sync')
            ->expectsOutputToContain('Responsen er ikke en PDF')
            ->assertExitCode(1);

        Storage::disk('fangenytt')->assertMissing('fangenytt-18.pdf');
    }

    public function testCommandSkipsExistingFileUnlessForced(): void
    {
        Storage::disk('fangenytt')->put('fangenytt-18.pdf', '%PDF-1.4 existing');
        Http::fake();

        $this->artisan('fangenytt:sync')
            ->expectsOutputToContain('hoppet over')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }
}
