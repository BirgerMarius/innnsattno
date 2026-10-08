<?php
namespace Tests\Feature;
use Tests\TestCase;
class PrintNoIndexTest extends TestCase { public function test_print_page_is_noindex_but_normal_page_is_not(): void { $this->get('/ordjakt/utskrift')->assertHeader('X-Robots-Tag', 'noindex, follow')->assertSee('name="robots" content="noindex,follow"', false); $this->get('/ordjakt')->assertHeaderMissing('X-Robots-Tag'); } }
