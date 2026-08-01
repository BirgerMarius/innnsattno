<?php

namespace Tests\Unit;

use App\Services\OnThisDayService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnThisDayServiceTest extends TestCase
{
    public function testItFiltersDuplicatesAndPrioritizesPeopleWithNorwegianPages(): void
    {
        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'events' => [$this->entry(1900, 'Hendelse', 'Hendelse', 'Q1')],
                'births' => [
                    $this->entry(2000, 'Nyere person', 'Nyere person', 'Q2'),
                    $this->entry(1900, 'Norsk person', 'Norsk person', 'Q3'),
                    $this->entry(1900, 'Norsk person', 'Norsk person', 'Q3'),
                ],
                'deaths' => [],
            ]),
            'www.wikidata.org/*' => Http::response([
                'entities' => [['id' => 'Q3', 'sitelinks' => ['nowiki' => ['title' => 'Norsk person']]]],
            ]),
        ]);

        $result = app(OnThisDayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertCount(2, $result['births']);
        $this->assertSame('Norsk person', $result['births'][0]['title']);
        $this->assertTrue($result['births'][0]['has_norwegian_page']);
        $this->assertSame('https://no.wikipedia.org/wiki/Norsk_person', $result['births'][0]['url']);
    }

    private function entry(int $year, string $text, string $title, string $id): array
    {
        return [
            'year' => $year,
            'text' => $text,
            'pages' => [[
                'wikibase_item' => $id,
                'titles' => ['normalized' => $title],
                'description' => 'Beskrivelse',
                'content_urls' => ['desktop' => ['page' => 'https://en.wikipedia.org/wiki/Test']],
            ]],
        ];
    }
}
