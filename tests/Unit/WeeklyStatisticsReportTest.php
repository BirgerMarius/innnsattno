<?php

namespace Tests\Unit;

use App\Services\AdminStatisticsSummary;
use App\Services\WeeklyStatisticsReport;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class WeeklyStatisticsReportTest extends TestCase
{
    public function testUsesExactIsoCalendarWeekAcrossNewYearAndDeduplicatedWeekMetrics(): void
    {
        $summary = Mockery::mock(AdminStatisticsSummary::class);
        $summary->shouldReceive('read')->andReturn($this->summary('2026-12-28', '2027-01-04 00:06:00 Europe/Oslo'));
        $report = (new WeeklyStatisticsReport($summary))->forWeek(Carbon::parse('2026-12-28', 'Europe/Oslo'));

        $this->assertTrue($report['ready']);
        $this->assertSame(53, $report['week_number']);
        $this->assertSame('2027-01-04', $report['week_end_exclusive']->toDateString());
        $this->assertSame(2, $report['metrics']['networks']);
        $this->assertSame(7, $report['prints_by_prison']['ringerike']);
        $this->assertSame(7, $report['prints_by_prison']['ilseng']);
        $this->assertCount(7, $report['days']);
    }

    public function testRefuses_stale_post_midnight_summary_and_marks_missing_day_unknown(): void
    {
        $summary = Mockery::mock(AdminStatisticsSummary::class);
        $data = $this->summary('2026-03-23', '2026-03-29 23:59:00 Europe/Oslo'); // DST week, before Monday boundary
        unset($data['daily']['2026-03-29']);
        $summary->shouldReceive('read')->andReturn($data);
        $report = (new WeeklyStatisticsReport($summary))->forWeek(Carbon::parse('2026-03-23', 'Europe/Oslo'));

        $this->assertFalse($report['ready']);
        $this->assertNull($report['days'][6]['pageviews']);
    }

    private function summary(string $week, string $generated): array
    {
        $start = Carbon::parse($week, 'Europe/Oslo')->startOfDay();
        $period = [
            'from' => $start, 'to' => $start->copy()->addDays(6),
            'suspected_human_pageviews' => 21, 'suspected_visitors' => 2, 'sessions' => 7, 'print_pageviews' => 14,
            'traffic_quality' => ['known_automated_technical_requests' => 2, 'other' => 1],
            'coverage' => ['complete' => true, 'covered_days' => 7, 'expected_days' => 7, 'classifier_versions' => [8]],
            'features' => [], 'comparison' => null,
            'pages' => [['name' => 'Forside', 'path' => '/tv', 'pageviews' => 7, 'unique_visitors' => 2]],
            'prints_by_prison' => ['ringerike' => 7, 'ilseng' => 7, 'other' => 0],
        ];
        $daily = [];
        for ($i = 0; $i < 7; $i++) $daily[$start->copy()->addDays($i)->toDateString()] = ['suspected_human_pageviews' => 3, 'sessions' => 1];
        return ['current_methodology' => true, 'generated_at' => Carbon::parse($generated), 'calendar_weeks' => [$week => $period], 'daily' => $daily];
    }
}
