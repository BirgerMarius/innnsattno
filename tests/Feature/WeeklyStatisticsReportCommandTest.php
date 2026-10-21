<?php

namespace Tests\Feature;

use App\Mail\WeeklyStatisticsReportMail;
use App\Services\WeeklyStatisticsReport;
use App\WeeklyStatisticsReportDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class WeeklyStatisticsReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testSendsOnceWithFakeTransportAndRecordsConfirmation(): void
    {
        config(['weekly_statistics.enabled' => true, 'weekly_statistics.recipient' => 'innsatt@innsatt.no']);
        Mail::fake();
        $service = Mockery::mock(WeeklyStatisticsReport::class);
        $service->shouldReceive('forWeek')->twice()->andReturn($this->report());
        $this->app->instance(WeeklyStatisticsReport::class, $service);

        $this->artisan('statistics:weekly-report', ['--week' => '2026-10-12'])->assertExitCode(0);
        $this->artisan('statistics:weekly-report', ['--week' => '2026-10-12'])->assertExitCode(0);

        Mail::assertSent(WeeklyStatisticsReportMail::class, 1);
        $this->assertSame(1, WeeklyStatisticsReportDelivery::count());
        $this->assertNotNull(WeeklyStatisticsReportDelivery::first()->sent_at);
    }

    private function report(): array
    {
        return ['ready' => true, 'subject' => 'test', 'week_start' => now('Europe/Oslo')->startOfWeek(),
            'week_end_exclusive' => now('Europe/Oslo')->startOfWeek()->addWeek(), 'metrics' => [], 'quality' => [],
            'days' => [], 'pages' => [], 'features' => [], 'prints_by_prison' => [], 'coverage' => [],
            'classifier_versions' => [], 'generated_at' => now(), 'comparison' => null, 'comparison_note' => null];
    }
}
