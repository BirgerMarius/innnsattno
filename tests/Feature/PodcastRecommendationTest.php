<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PodcastRecommendationTest extends TestCase
{
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
            ->assertSee('Les mer og lytt ↗')
            ->assertSee('src="'.asset('img/podcast/betjenten-og-psyken.png').'"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer">Les mer og lytt ↗', false)
            ->assertSeeInOrder(['Fangenytt', 'podcast-recommendation-card', 'Premier League']);

        Carbon::setTestNow(Carbon::parse('2026-11-05 08:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('podcast-recommendation-card', false);

        Carbon::setTestNow(Carbon::parse('2026-11-05 09:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('podcast-recommendation-card', false);
        Carbon::setTestNow();
    }
}
