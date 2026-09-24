<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrintReturnTest extends TestCase
{
    public function test_wordsearch_print_returns_to_the_exact_parameterized_source_url(): void
    {
        $source = '/ordjakt?kategori=dyr&side=2#rutenett';

        $this->get('/ordjakt/utskrift?'.http_build_query([
            'kategori' => 'dyr',
            'return_to' => $source,
        ]))
            ->assertOk()
            ->assertSee('const returnUrl = '.json_encode($source, JSON_UNESCAPED_SLASHES).';', false)
            ->assertSee('window.location.replace(returnUrl);', false)
            ->assertSee('window.print();', false);
    }

    public function test_print_pages_do_not_accept_another_print_page_as_the_return_url(): void
    {
        $this->get('/ordjakt/utskrift?'.http_build_query([
            'kategori' => 'dyr',
            'return_to' => '/ordjakt/utskrift?kategori=dyr',
        ]))
            ->assertOk()
            ->assertSee('const returnUrl = "/ordjakt?kategori=dyr";', false)
            ->assertDontSee('const returnUrl = "/ordjakt/utskrift', false);
    }
}
