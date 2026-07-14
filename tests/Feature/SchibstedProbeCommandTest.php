<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchibstedProbeCommandTest extends TestCase
{
    /** @test */
    public function probe_uses_explicit_candidate_list(): void
    {
        Http::fake(['*schedule' => Http::response(['events' => []], 200), '*' => Http::response([], 404)]);

        $this->artisan('football:schibsted-probe', [
            '--profile' => 'season',
            '--season' => 7767,
            '--delay' => 250,
        ])->expectsOutputToContain('Planlagte kall: 9')
            ->assertExitCode(0);

        Http::assertSentCount(9);
    }

    /** @test */
    public function probe_requires_event_id_for_event_profile(): void
    {
        $this->artisan('football:schibsted-probe', ['--profile' => 'event'])
            ->expectsOutputToContain('--event må settes')
            ->assertExitCode(22);
    }

    /** @test */
    public function probe_classifies_200_403_and_404(): void
    {
        Http::fake([
            '*schedule' => Http::response(['events' => []], 200),
            '*standings' => Http::response(['message' => 'Forbidden'], 403),
            '*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->artisan('football:schibsted-probe', [
            '--profile' => 'season',
            '--season' => 7767,
            '--delay' => 250,
        ])->expectsOutputToContain('bekreftet')
            ->expectsOutputToContain('avvist')
            ->assertExitCode(0);
    }
}
