<?php

namespace Tests\Unit;

use App\Services\NamedayService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NamedayServiceTest extends TestCase
{
    public function testItNormalizesAndDeduplicatesNamesForTheRequestedDate(): void
    {
        Http::fake(['webapi.no/*' => Http::response([
            'data' => [['month' => 8, 'day' => 1, 'names' => [' Peder ', 'Petra', 'Peder', '']]],
        ])]);

        $result = app(NamedayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertSame(['Peder', 'Petra'], $result['names']);
    }
}
