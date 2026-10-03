<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FangenyttTest extends TestCase
{
    public function testFangenyttPageShowsAvailableIssuesWithOriginalPdfLinks(): void
    {
        $response = $this->get('/fangenytt');

        $response
            ->assertOk()
            ->assertSee('Fangenytt')
            ->assertSee('Fangenytt nr. 18')
            ->assertSee('Fangenytt nr. 1')
            ->assertSee('Åpne / skriv ut')
            ->assertSee('https://www.fangeforeningen.no/wp-content/uploads/2026/10/Fangenytt-magasin-18-pdf.pdf', false)
            ->assertSee('https://www.fangeforeningen.no/wp-content/uploads/2026/05/Fangenytt-nr.-16.pdf', false)
            ->assertSee('https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev.pdf', false)
            ->assertSee('href="https://www.fangeforeningen.no/" target="_blank" rel="noopener noreferrer"', false);

        foreach (Config::get('fangenytt.issues') as $issue) {
            $this->assertStringStartsWith('https://www.fangeforeningen.no/', $issue['url']);
            $response->assertSee('href="'.$issue['url'].'"', false);
        }
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
