<?php

namespace Tests\Unit;

use App\Services\TodayContentService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TodayContentServiceTest extends TestCase
{
    public function testItRanksNorwegianContentSeparatelyAndRejectsEnglishAndObscureItems(): void
    {
        Http::fake(['www.wikidata.org/*' => Http::response(['entities' => [
            $this->entity('Q1', 'Norsk hendelse', 'En viktig hendelse i Norge', true, 2),
            $this->entity('Q2', 'Stor verdenshendelse', 'En hendelse som endret verden', false, 45),
            $this->entity('Q3', 'Ukjent hendelse', 'En liten lokal hendelse', false, 2),
        ]])]);

        $result = app(TodayContentService::class)->curate(['events' => [
            $this->item('Q2', 'Den store hendelsen ble kjent i hele verden'),
            $this->item('Q1', 'En norsk hendelse fant sted i Norge'),
            $this->item('Q3', 'En liten hendelse fant sted i en by'),
            $this->item('Q4', 'The event was held in a small town'),
            $this->item('Q1', 'En norsk hendelse fant sted i Norge'),
        ]], CarbonImmutable::parse('2026-03-12'));

        $this->assertSame('Norsk hendelse', $result['norway_events'][0]['title']);
        $this->assertSame('Stor verdenshendelse', $result['world_events'][0]['title']);
        $this->assertCount(1, $result['norway_events']);
        $this->assertCount(1, $result['world_events']);
        $this->assertStringNotContainsString('The event', json_encode($result));
    }

    public function testFinishedResultIsRefreshedAndLastSuccessSurvivesFailure(): void
    {
        Http::fake([
            'no.wikipedia.org/*' => Http::response(['events' => [[
                'year' => 1900, 'text' => 'En hendelse i Norge',
                'pages' => [['wikibase_item' => 'Q1', 'titles' => ['normalized' => 'Cachehendelse'], 'description' => 'En hendelse i Norge']],
            ]]]),
            'www.wikidata.org/*' => Http::response(['entities' => [$this->entity('Q1', 'Cachehendelse', 'En hendelse i Norge', true, 2)]]),
        ]);
        $service = app(TodayContentService::class);
        $date = CarbonImmutable::parse('2026-03-12');
        $this->assertSame('Cachehendelse', $service->forDate($date)['norway_events'][0]['title']);

        Cache::forget('today.fresh.curated.v3.03-12');
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('nede'));
        $this->assertSame('Cachehendelse', $service->forDate($date)['norway_events'][0]['title']);
    }

    /** @dataProvider ordinaryDates */
    public function testOrdinaryDatesCanReceiveAutomaticContentWithoutLocalArchive(string $date): void
    {
        Http::fake(['www.wikidata.org/*' => Http::response(['entities' => [
            $this->entity('Q1', 'Norsk hendelse', 'En viktig hendelse i Norge', true, 2),
            $this->entity('Q2', 'Verdenshendelse', 'En stor hendelse i verden', false, 45),
        ]])]);

        $result = app(TodayContentService::class)->curate(['events' => [
            $this->item('Q1', 'En viktig hendelse i Norge'),
            $this->item('Q2', 'En stor hendelse i verden'),
        ]], CarbonImmutable::parse($date));

        $this->assertNotEmpty($result['norway_events']);
        $this->assertNotEmpty($result['world_events']);
    }

    public function ordinaryDates(): array
    {
        return [
            '10 August' => ['2026-08-10'], '22 February' => ['2027-02-22'],
            '12 January' => ['2026-01-12'], '9 March' => ['2026-03-09'],
            '14 April' => ['2026-04-14'], '18 September' => ['2026-09-18'],
            '11 November' => ['2026-11-11'], 'Norwegian history date' => ['2026-05-17'],
            'international history date' => ['2026-07-20'],
        ];
    }

    private function item(string $id, string $text): array
    {
        return ['year' => 1900, 'title' => 'Kandidat', 'text' => $text, 'description' => '', 'url' => null, 'wikibase_id' => $id, 'page_length' => $id === 'Q3' ? 1000 : 100000];
    }

    private function entity(string $id, string $title, string $description, bool $norwegian, int $links): array
    {
        $sitelinks = ['nowiki' => ['title' => $title]];
        for ($i = 0; $i < $links; $i++) $sitelinks['site'.$i] = ['title' => $title];
        return ['id' => $id, 'labels' => ['nb' => ['value' => $title]], 'descriptions' => ['nb' => ['value' => $description]], 'sitelinks' => $sitelinks,
            'claims' => $norwegian ? ['P27' => [['mainsnak' => ['datavalue' => ['value' => ['id' => 'Q20']]]]]] : []];
    }
}
