<?php

namespace Tests\Feature;

use App\Services\PrisonContext;
use Tests\TestCase;

class PrisonNavigationContextTest extends TestCase
{
    public function test_shared_pages_build_the_home_link_from_the_url_context(): void
    {
        foreach (['ringerike', 'ilseng', 'kongsvinger'] as $prison) {
            $this->get('/tidsfordriv?prison='.$prison)
                ->assertOk()
                ->assertSee('data-prison-context="'.$prison.'"', false)
                ->assertSee('href="/'.$prison.'"', false);
        }
    }

    public function test_each_front_page_keeps_its_context_for_shared_and_prefixed_pages(): void
    {
        foreach (['ringerike', 'ilseng', 'kongsvinger'] as $prison) {
            $this->get('/'.$prison)
                ->assertOk()
                ->assertSee('href="/tidsfordriv?prison='.$prison.'"', false)
                ->assertSee('href="/'.$prison.'/ukens-fotball-pa-tv"', false);

            $this->get('/'.$prison.'/ukens-fotball-pa-tv')
                ->assertOk()
                ->assertSee('href="/'.$prison.'"', false);
        }
    }

    public function test_direct_shared_page_visit_defaults_to_ringerike(): void
    {
        $this->get('/tidsfordriv')
            ->assertOk()
            ->assertSee('data-prison-context="ringerike"', false)
            ->assertSee('href="/ringerike"', false);
    }

    public function test_prison_prefix_cannot_be_overridden_by_query_context(): void
    {
        $this->get('/ilseng/ukens-fotball-pa-tv?prison=ringerike')
            ->assertOk()
            ->assertSee('href="/ilseng"', false)
            ->assertSee('data-prison-context="ilseng"', false);
    }

    public function test_shared_link_builder_preserves_query_and_prefixed_urls(): void
    {
        $this->get('/tidsfordriv?prison=kongsvinger');
        $context = app(PrisonContext::class);

        $this->assertSame('/quiz?difficulty=easy&prison=kongsvinger', $context->contextUrl('/quiz?difficulty=easy'));
        $this->assertSame('/ilseng/bonnetider', $context->contextUrl('/ilseng/bonnetider'));
    }

    public function test_shared_link_builder_adds_context_to_local_absolute_urls(): void
    {
        $this->get('/tidsfordriv?prison=ilseng');

        $this->assertSame(url('/quiz').'?prison=ilseng', app(PrisonContext::class)->contextUrl(url('/quiz')));
    }
}
