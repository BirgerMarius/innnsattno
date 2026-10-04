<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FangenyttTest extends TestCase
{
    public function testFangenyttPageShowsAvailableIssuesWithLocalPdfLinks(): void
    {
        $response = $this->get('/fangenytt');

        $response
            ->assertOk()
            ->assertSee('Fangenytt')
            ->assertSee('Fangenytt nr. 18')
            ->assertSee('Fangenytt nr. 1')
            ->assertSee('Åpne / skriv ut')
            ->assertSee('href="https://www.fangeforeningen.no/" target="_blank" rel="noopener noreferrer"', false);

        foreach (Config::get('fangenytt.issues') as $issue) {
            $this->assertStringStartsWith('https://www.fangeforeningen.no/', $issue['original_url']);
            $response->assertSee('href="'.route('fangenytt.pdf', ['number' => $issue['number']]).'"', false);
        }
    }

    public function testPdfRouteRejectsUnknownOrMissingIssues(): void
    {
        Storage::fake('fangenytt');

        $this->get('/fangenytt/999/pdf')->assertNotFound();
        $this->get('/fangenytt/18/pdf')->assertNotFound();
        $this->get('/fangenytt/18/../pdf')->assertNotFound();
    }

    public function testPdfRouteServesRegisteredLocalPdfInline(): void
    {
        Storage::fake('fangenytt');
        Storage::disk('fangenytt')->put('fangenytt-18.pdf', '%PDF-1.4 test');

        $this->get(route('fangenytt.pdf', ['number' => 18]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="fangenytt-18.pdf"');
    }

    public function testHomepageLinksToFangenytt(): void
    {
        $response = $this->get(route('tv'));

        $response
            ->assertOk()
            ->assertSee('Fangenytt')
            ->assertSee('href="'.route('fangenytt.index').'"', false)
            ->assertSee('front-page-btn--fangenytt', false)
            ->assertSeeInOrder([
                'ℹ️ Ilseng fengsel',
                'Fangenytt',
                'Premier League',
            ]);

        $css = file_get_contents(public_path('css/custom/app.css'));
        $this->assertMatchesRegularExpression('/\.front-page-btn--fangenytt\s*\{[^}]*--front-page-btn-bg:\s*#7c2d12;[^}]*--front-page-btn-color:\s*#fff;[^}]*flex-direction:\s*column;/s', $css);
        $this->assertMatchesRegularExpression('/front-page-btn--fangenytt[^>]*>\s*<span class="front-page-btn-title">.*?Fangenytt.*?<\/span>\s*<small>Magasin for innsatte – klar til utskrift<\/small>/s', (string) $response->getContent());
    }

    public function testHomepageShowsFangenyttNewBadgeForFourteenDays(): void
    {
        Config::set('fangenytt.published_at', '2026-10-03 10:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-03 09:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('front-page-new-badge--fangenytt', false);

        Carbon::setTestNow(Carbon::parse('2026-10-17 09:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('front-page-new-badge--fangenytt', false)->assertSee('>Nyhet<', false);

        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('front-page-new-badge--fangenytt', false);
        Carbon::setTestNow();
    }

    public function testHomepageWorksWithoutFangenyttPublicationTime(): void
    {
        Config::set('fangenytt.published_at', null);

        $this->get(route('tv'))
            ->assertOk()
            ->assertDontSee('front-page-new-badge--fangenytt', false);
    }
}
