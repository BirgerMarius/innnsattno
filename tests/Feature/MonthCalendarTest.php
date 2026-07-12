<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class MonthCalendarTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testCalendarChoicePageIsAvailable()
    {
        $response = $this->get('/manedskalender');

        $response
            ->assertStatus(200)
            ->assertSee('Månedskalender – For utskrift')
            ->assertSee('Denne måneden')
            ->assertSee('Resten av året');
    }

    public function testCurrentMonthCalendarOnlyShowsCurrentMonth()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 12, 0, 0, 'Europe/Oslo'));

        $response = $this->get('/manedskalender/utskrift?periode=current_month');

        $response
            ->assertStatus(200)
            ->assertSee('Juli 2026')
            ->assertDontSee('August 2026')
            ->assertSee('Skriv ut kalender')
            ->assertSee('Generert fra Innsatt.no');
    }

    public function testRestOfYearCalendarShowsThroughDecember()
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 12, 12, 0, 0, 'Europe/Oslo'));

        $response = $this->get('/manedskalender/utskrift?periode=rest_of_year');

        $response
            ->assertStatus(200)
            ->assertSee('November 2026')
            ->assertSee('Desember 2026')
            ->assertDontSee('Januar 2027');
    }

    public function testCalendarShowsNorwegianHolidays()
    {
        Carbon::setTestNow(Carbon::create(2024, 3, 12, 12, 0, 0, 'Europe/Oslo'));

        $response = $this->get('/manedskalender/utskrift?periode=current_month');

        $response
            ->assertStatus(200)
            ->assertSee('Mars 2024')
            ->assertSee('Skjærtorsdag')
            ->assertSee('Langfredag')
            ->assertSee('1. påskedag');
    }

    public function testCalendarShowsFixedNorwegianHolidaysForRestOfYear()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 12, 12, 0, 0, 'Europe/Oslo'));

        $response = $this->get('/manedskalender/utskrift?periode=rest_of_year');

        $response
            ->assertStatus(200)
            ->assertSee('Grunnlovsdag')
            ->assertSee('1. juledag')
            ->assertSee('2. juledag');
    }

    public function testCalendarHandlesLeapYear()
    {
        Carbon::setTestNow(Carbon::create(2024, 2, 12, 12, 0, 0, 'Europe/Oslo'));

        $response = $this->get('/manedskalender/utskrift?periode=current_month');

        $response
            ->assertStatus(200)
            ->assertSee('Februar 2024')
            ->assertSee('29');
    }

    public function testCalendarPeriodIsValidated()
    {
        $response = $this->from('/manedskalender')
            ->get('/manedskalender/utskrift?periode=hele_tiden');

        $response
            ->assertRedirect('/manedskalender')
            ->assertSessionHasErrors('periode');
    }
}
