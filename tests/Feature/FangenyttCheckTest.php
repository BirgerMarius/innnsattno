<?php

namespace Tests\Feature;

use App\FangenyttIssue;
use App\Services\FangenyttCheck;
use App\Services\FangenyttCoverGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FangenyttCheckTest extends TestCase
{
    use RefreshDatabase;
    private const PDF = 'https://www.fangeforeningen.no/wp-content/uploads/2026/11/Fangenytt-nr.-19.pdf';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fangenytt');
        FangenyttIssue::create(['number' => 18, 'original_url' => 'https://www.fangeforeningen.no/18.pdf', 'local_file' => 'fangenytt-18.pdf', 'cover_file' => 'covers/fangenytt-18.jpg', 'status' => 'published', 'source' => 'seed']);
        Storage::disk('fangenytt')->put('fangenytt-18.pdf', 'old');
        $cover = Mockery::mock(FangenyttCoverGenerator::class);
        $cover->shouldReceive('generate')->andReturnUsing(function ($pdf, $path) { Storage::disk('fangenytt')->put($path, 'cover'); return ['success' => true]; });
        $this->app->instance(FangenyttCoverGenerator::class, $cover);
        $this->travelTo(now()->startOfDay());
    }

    private function pdf(): string
    {
        $stream = 'BT /F1 20 Tf 50 700 Td (Nr. 19) Tj ET';
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }

    private function fakeSource($pdf, int $status = 200): void
    {
        Http::fake([FangenyttCheck::SOURCE => Http::response('<figure><a href="'.self::PDF.'"><img></a></figure>', 200), self::PDF => Http::response($pdf, $status)]);
    }

    public function testPublishesOnceAndUsesTwoDayInterval(): void
    {
        $this->fakeSource($this->pdf());
        $check = app(FangenyttCheck::class);
        $this->assertTrue($check->run()['success']);
        $this->assertTrue($check->status()['next']->equalTo(now()->addDays(2)));
        $issue = FangenyttIssue::where('number', 19)->firstOrFail();
        $published = $issue->published_locally_at->toDateTimeString();
        Storage::disk('fangenytt')->assertExists('fangenytt-19.pdf');
        $this->withSession(['admin_authenticated' => true])->get('/adm')->assertOk()->assertSee('Ny utgave av Fangenytt er tilgjengelig')->assertSee('Fangenytt nr. 19')->assertSee(route('fangenytt.pdf', ['number' => 19]))->assertSee($check->status()['next']->copy()->setTimezone('Europe/Oslo')->format('d.m.Y H:i'));
        $this->assertTrue($check->run(true)['success']);
        $this->assertSame($published, $issue->fresh()->published_locally_at->toDateTimeString());
        Http::fake();
        $this->travel(1)->days();
        $this->assertTrue($check->run()['success']);
        Http::assertNothingSent();
        $this->travel(1)->days();
        $this->fakeSource($this->pdf());
        $this->assertTrue($check->run()['success']);
        Http::assertSentCount(1);
        $this->assertSame($published, $issue->fresh()->published_locally_at->toDateTimeString());
    }

    public function testExistingSuccessfulCheckUsesNewIntervalWithoutChangingStoredStatus(): void
    {
        $lastSuccess = now()->subDays(3);
        \Illuminate\Support\Facades\DB::table('fangenytt_check_status')->insert(['id' => 1, 'last_success_at' => $lastSuccess, 'last_attempt_at' => $lastSuccess]);
        $check = app(FangenyttCheck::class);
        $this->assertTrue($check->status()['next']->equalTo($lastSuccess->copy()->addDays(2)));
        $this->assertDatabaseHas('fangenytt_check_status', ['id' => 1, 'last_success_at' => $lastSuccess->toDateTimeString()]);
        Http::fake([FangenyttCheck::SOURCE => Http::response('error', 503)]);
        $this->assertFalse($check->run()['success']);
        $this->assertTrue($check->status()['next']->equalTo(now()->addDay()));
    }

    public function testInvalidOrInterruptedPdfDoesNotPublishAndRetriesNextDay(): void
    {
        $this->fakeSource('%PDF-1.7 broken');
        $check = app(FangenyttCheck::class);
        $this->assertFalse($check->run()['success']);
        $this->assertDatabaseMissing('fangenytt_issues', ['number' => 19, 'status' => 'published']);
        Storage::disk('fangenytt')->assertMissing('fangenytt-19.pdf');
        Storage::disk('fangenytt')->assertExists('fangenytt-18.pdf');
        $this->assertNotNull($check->status()['error']);
        $this->fakeSource($this->pdf(), 500);
        $this->travel(1)->days();
        $this->assertFalse($check->run()['success']);
    }

    public function testInterruptedDownloadWithWrongLengthIsRejected(): void
    {
        Http::fake([FangenyttCheck::SOURCE => Http::response('<a href="'.self::PDF.'">PDF</a>'), self::PDF => Http::response($this->pdf(), 200, ['Content-Length' => '999999'])]);
        $result = app(FangenyttCheck::class)->run();
        $this->assertFalse($result['success']);
        $this->assertDatabaseMissing('fangenytt_issues', ['number' => 19]);
        Storage::disk('fangenytt')->assertMissing('fangenytt-19.pdf');
    }

    public function testEmptySourceDoesNotCountAsSuccess(): void
    {
        Http::fake([FangenyttCheck::SOURCE => Http::response('<html>Endret side</html>')]);
        $result = app(FangenyttCheck::class)->run();
        $this->assertFalse($result['success']);
        $this->assertNull(app(FangenyttCheck::class)->status()['success']);
        Storage::disk('fangenytt')->assertExists('fangenytt-18.pdf');
    }

    public function testSourceFailurePreservesArchiveAndLastSuccess(): void
    {
        \Illuminate\Support\Facades\DB::table('fangenytt_check_status')->insert(['id' => 1, 'last_success_at' => now()->subDays(14), 'last_attempt_at' => now()->subDays(14)]);
        $check = app(FangenyttCheck::class);
        $success = $check->status()['success']->toDateTimeString();
        Http::fake([FangenyttCheck::SOURCE => Http::response('error', 503)]);
        $this->assertFalse($check->run()['success']);
        $this->assertSame($success, $check->status()['success']->toDateTimeString());
        Storage::disk('fangenytt')->assertExists('fangenytt-18.pdf');
    }
}
