<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Tests\TestCase;

class DailyWordPrintTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function testPrintPageKeepsTheWordSelectedByItsIdAcrossADateChange(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-01 23:59:00', 'Europe/Oslo'));
        $this->get(route('daily-word.print', ['word' => 'rolig-start']))
            ->assertOk()
            ->assertSee('Det er lov å ta en rolig start.')
            ->assertSee('window.print()', false)
            ->assertSee('innsatt.no');

        $this->travelTo(CarbonImmutable::parse('2026-01-02 00:01:00', 'Europe/Oslo'));
        $this->get(route('daily-word.print', ['word' => 'rolig-start']))
            ->assertOk()
            ->assertSee('Det er lov å ta en rolig start.')
            ->assertSee('daily-word-print-sheet', false);
    }

    public function testUnknownPrintIdReturnsNotFound(): void
    {
        $this->get(route('daily-word.print', ['word' => 'finnes-ikke']))->assertNotFound();
    }
}
