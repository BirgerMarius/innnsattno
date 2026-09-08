<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_football_pages_render_when_sportsnext_is_unavailable(): void
    {
        Http::fake(['api.sportsnext.schibsted.io/*' => Http::response([], 500)]);

        $this->get('/football')->assertOk()->assertSee('Fotball-VM 2026');
        $this->get('/fotball-utskrift')->assertOk()->assertSee('Fotball-VM 2026');
    }
}
