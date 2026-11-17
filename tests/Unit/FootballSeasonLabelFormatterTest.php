<?php

namespace Tests\Unit;

use App\Services\FootballSeasonLabelFormatter;
use PHPUnit\Framework\TestCase;

class FootballSeasonLabelFormatterTest extends TestCase
{
    /** @dataProvider seasonNames */
    public function test_it_keeps_only_the_actual_season_years_from_provider_labels(string $providerName, string $expected): void
    {
        $this->assertSame($expected, (new FootballSeasonLabelFormatter())->season($providerName));
    }

    public function seasonNames(): array
    {
        return [
            ['Premier League 2026/2027', '2026/2027'],
            ['Eliteserien 2026', '2026'],
            ['OBOS-ligaen 2026', '2026'],
            ['LaLiga 2026/2027', '2026/2027'],
            ['Bundesliga 2026/2027', '2026/2027'],
            ['Champions League 2026/2027', '2026/2027'],
            ['Europa League 2026/2027', '2026/2027'],
            ['UEFA Europa Conference League 2026/2027', '2026/2027'],
            ['UEFA Nations League 2026/2028', '2026/2028'],
            ['2026/27', '2026/27'],
        ];
    }

    public function test_it_preserves_missing_season_metadata_fallbacks(): void
    {
        $formatter = new FootballSeasonLabelFormatter();

        $this->assertSame('Sesong-ID: 9186', $formatter->season('Sesong-ID: 9186'));
        $this->assertNull($formatter->season(null));
    }
}
