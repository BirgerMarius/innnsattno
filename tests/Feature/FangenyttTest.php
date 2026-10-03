<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
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
            ->assertSee('https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev.pdf', false);

        foreach (Config::get('fangenytt.issues') as $issue) {
            $this->assertStringStartsWith('https://www.fangeforeningen.no/', $issue['url']);
            $response->assertSee('href="'.$issue['url'].'"', false);
        }
    }

    public function testHomepageLinksToFangenytt(): void
    {
        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Fangenytt')
            ->assertSee('href="'.route('fangenytt.index').'"', false);
    }
}
