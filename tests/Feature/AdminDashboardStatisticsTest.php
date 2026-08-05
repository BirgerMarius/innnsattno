<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private $summaryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->summaryPath = storage_path('framework/testing/admin-summary-' . uniqid() . '.json');
        config()->set('admin.statistics_summary_path', $this->summaryPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->summaryPath);
        parent::tearDown();
    }

    public function testDashboardShowsValidatedStatisticsSummary()
    {
        $this->writeSummary();

        $response = $this->withSession(['admin_authenticated' => true])->get('/adm');

        $response->assertOk();
        $response->assertSee('Sidevisninger 04.08.2026');
        $response->assertSee('1 234');
        $response->assertSee('Unike besøkende 04.08.2026');
        $response->assertSee('321');
        $response->assertSee('8 765');
        $response->assertSee('12 345');
        $response->assertSee('/nyheter');
        $response->assertSee('04.08.2026 12:15');
        $response->assertSee('TESTDATA – ikke produksjonstall');
    }

    public function testDashboardShowsFallbackWhenSummaryIsMissingOrInvalid()
    {
        $admin = $this->withSession(['admin_authenticated' => true]);
        $admin->get('/adm')->assertOk()->assertSee('Statistikk er ikke tilgjengelig akkurat nå');

        file_put_contents($this->summaryPath, '{ikke gyldig json');
        $admin->get('/adm')->assertOk()->assertSee('Statistikk er ikke tilgjengelig akkurat nå');
    }

    public function testDashboardNeverExposesIpAddressesFromSummary()
    {
        $this->writeSummary([
            'unexpected_ip' => '203.0.113.42',
            'last_7_days' => [
                'from' => '2026-07-29', 'to' => '2026-08-04',
                'pageviews' => 8765, 'requests' => 12345,
                'top_page' => ['path' => '/server/203.0.113.42', 'pageviews' => 456],
            ],
        ]);

        $response = $this->withSession(['admin_authenticated' => true])->get('/adm');

        $response->assertOk();
        $response->assertSee('Statistikk er ikke tilgjengelig akkurat nå');
        $response->assertDontSee('203.0.113.42');
    }

    public function testDashboardShowsTopPagesForSelectedPeriod()
    {
        $this->writeSummary([
            'schema_version' => 2,
            'top_pages' => $this->topPages(),
        ]);

        $response = $this->withSession(['admin_authenticated' => true])->get('/adm?traffic_period=30');

        $response->assertOk();
        $response->assertSee('Alle offentlige sider');
        $response->assertSee('Bønnetider Ilseng');
        $response->assertSee('href="' . url('/bonnetider-ilseng') . '"', false);
        $response->assertSee('3 000');
        $response->assertSee('42');
        $response->assertSee('flere brukere kan dele samme offentlige IP');
        $response->assertSee('traffic_period=30', false);
        $response->assertSee('Side 11');
    }

    public function testDashboardRejectsUnsortedOrIpContainingRankings()
    {
        $periods = $this->topPages();
        $periods['7']['pages'][] = ['name' => 'Ugyldig', 'path' => '/server/203.0.113.5', 'pageviews' => 4000, 'unique_visitors' => 1];
        $this->writeSummary(['schema_version' => 2, 'top_pages' => $periods]);

        $response = $this->withSession(['admin_authenticated' => true])->get('/adm');
        $response->assertOk()->assertSee('Alle sider er ikke tilgjengelige ennå')->assertDontSee('203.0.113.5');
    }

    public function testAllThreeTrafficPeriodsStillWork()
    {
        $this->writeSummary(['schema_version' => 2, 'top_pages' => $this->topPages()]);
        foreach (['1' => '100', '7' => '700', '30' => '3 000'] as $period => $views) {
            $this->withSession(['admin_authenticated' => true])
                ->get('/adm?traffic_period=' . $period)
                ->assertOk()->assertSee($views)->assertSee('aria-current="true"', false);
        }
    }

    private function writeSummary(array $overrides = []): void
    {
        $summary = [
            'schema_version' => 1,
            'generated_at' => '2026-08-04T12:15:00+00:00',
            'test_data' => true,
            'latest_day' => ['date' => '2026-08-04', 'pageviews' => 1234, 'unique_visitors' => 321],
            'last_7_days' => [
                'from' => '2026-07-29', 'to' => '2026-08-04',
                'pageviews' => 8765, 'requests' => 12345,
                'top_page' => ['path' => '/nyheter', 'pageviews' => 456],
            ],
        ];
        $summary = array_replace($summary, $overrides);
        file_put_contents($this->summaryPath, json_encode($summary));
    }

    private function topPages(): array
    {
        $periods = [];
        foreach ([1, 7, 30] as $days) {
            $periods[(string) $days] = [
                'from' => $days === 1 ? '2026-08-04' : ($days === 7 ? '2026-07-29' : '2026-07-06'),
                'to' => '2026-08-04',
                'pages' => array_merge([[
                    'name' => 'Bønnetider Ilseng', 'path' => '/bonnetider-ilseng',
                    'pageviews' => $days === 30 ? 3000 : ($days === 7 ? 700 : 100), 'unique_visitors' => 42,
                ]], array_map(function ($number) {
                    return ['name' => 'Side ' . $number, 'path' => sprintf('/side-%02d', $number),
                        'pageviews' => 90 - $number, 'unique_visitors' => 20 - $number];
                }, range(1, 11))),
            ];
        }
        return $periods;
    }
}
