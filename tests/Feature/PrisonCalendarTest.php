<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrisonCalendarTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_prison_links_and_existing_print_calendar(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-31 12:00', 'Europe/Oslo'));
        Carbon::setTestNow(Carbon::parse('2026-12-31 12:00', 'Europe/Oslo'));
        foreach (['ringerike', 'ilseng', 'kongsvinger'] as $prison) {
            $this->get('/'.$prison)->assertOk()->assertSee('/'.$prison.'/kalender', false)->assertSee('Åpne kalender, uke 53')->assertSee('/manedskalender', false);
            $this->get('/'.$prison.'/kalender')->assertOk()->assertSee(route('prison.home', ['prison' => $prison]), false)->assertSee('Desember 2026')->assertSee('uke 53');
        }
        $this->get('/manedskalender')->assertOk();
    }

    public function test_year_boundary_iso_week_and_holidays(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2021-01-01 12:00', 'Europe/Oslo'));
        $this->get('/ilseng/kalender?year=2021&month=1')->assertOk()->assertSee('uke 53')->assertSee('Helligdag: 1. nyttårsdag')->assertSee('year=2020&amp;month=12', false);
        $this->get('/ilseng/kalender?year=2024&month=3')->assertOk()->assertSee('Helligdag: Skjærtorsdag');
        $this->get('/ilseng/kalender?year=2026&month=4')->assertOk()->assertSee('Helligdag: 1. påskedag')->assertSee('Offisiell flaggdag: 1. påskedag');
    }

    public function test_date_tools_count_inclusive_days_and_cross_leap_year(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2024-02-29 12:00', 'Europe/Oslo'));
        $this->get('/kongsvinger/kalender?year=2024&month=2&tool=distance&date=2024-02-29')->assertOk()->assertSee('I dag')->assertSee('0 uker og 0 dager');
        $this->get('/kongsvinger/kalender?year=2024&month=2&tool=period&from=2024-02-29&to=2024-02-29&weekday=4')->assertOk()->assertSee('1 kalenderdager')->assertSee('Antall torsdager: 1');
        $this->get('/kongsvinger/kalender?year=2024&month=2&tool=period&from=2024-03-01&to=2024-02-29&weekday=0')->assertOk()->assertSee('Sluttdatoen må være på eller etter startdatoen.');
        $this->get('/kongsvinger/kalender?year=2024&month=12&tool=shift&start=2024-12-31&days=1&direction=after')->assertOk()->assertSee('1. januar 2025')->assertSee('selected=2025-01-01');
        $this->get('/kongsvinger/kalender?year=2024&month=2&selected=2024-02-29')->assertOk()->assertSee('Valgt dato');
    }
}
