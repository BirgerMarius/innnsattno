<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PodcastRecommendationTest extends TestCase
{
    public function testPodcastReadMoreRedirectUsesTheFixedTargetWithoutCaching(): void
    {
        $response = $this->get('/podkast/betjenten-og-psyken/les-mer?target=https://example.test');

        $response
            ->assertStatus(302)
            ->assertHeader('Location', 'https://www.sykehuset-innlandet.no/podkast/betjenten-og-psyken/');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function testPodcastListenRedirectUsesTheFixedTargetWithoutCaching(): void
    {
        $response = $this->get('/podkast/betjenten-og-psyken/hor?target=https://example.test');

        $response
            ->assertStatus(302)
            ->assertHeader('Location', 'https://open.spotify.com/show/4DCB36GYqnR3D1d9WIBMHe');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function testPodcastCoverUsesTheCompleteSquareOriginal(): void
    {
        $dimensions = getimagesize(public_path('img/podcast/betjenten-og-psyken.png'));

        $this->assertSame(1436, $dimensions[0]);
        $this->assertSame(1436, $dimensions[1]);
    }

    public function testPodcastRecommendationIsHiddenWithoutPublicationTime(): void
    {
        Config::set('podcast_recommendation.published_at', null);

        $this->get(route('tv'))
            ->assertOk()
            ->assertDontSee('podcast-recommendation-card', false);
    }

    public function testPodcastRecommendationIsShownForFourWeeksFromTheOsloPublicationTime(): void
    {
        Config::set('podcast_recommendation.published_at', '2026-10-08 09:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-08 08:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('podcast-recommendation-card', false);

        Carbon::setTestNow(Carbon::parse('2026-10-08 09:00:00', 'Europe/Oslo'));
        $response = $this->get(route('tv'));
        $response
            ->assertOk()
            ->assertSee('podcast-recommendation-card', false)
            ->assertSee('Anbefales')
            ->assertSee('Betjenten og psyken')
            ->assertSee('Samtaler om psykisk helse i fengsel med ansatte i kriminalomsorgen og terapeuter.')
            ->assertSee('En podkast fra Sykehuset Innlandet')
            ->assertSee('Les mer ↗')
            ->assertSee('Hør ↗')
            ->assertSee('src="'.asset('img/podcast/betjenten-og-psyken.png').'"', false)
            ->assertSee('href="/podkast/betjenten-og-psyken/les-mer" target="_blank" rel="noopener noreferrer">Les mer ↗', false)
            ->assertSee('href="/podkast/betjenten-og-psyken/hor" target="_blank" rel="noopener noreferrer">Hør ↗', false)
            ->assertSeeInOrder(['front-page-date', 'podcast-recommendation-card', 'Skriv ut TV-guide – Ringerike fengsel']);

        Carbon::setTestNow(Carbon::parse('2026-11-05 08:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('podcast-recommendation-card', false);

        Carbon::setTestNow(Carbon::parse('2026-11-05 09:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('podcast-recommendation-card', false);
        Carbon::setTestNow();
    }
}
