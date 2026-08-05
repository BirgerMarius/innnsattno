<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TvGuidePrintTest extends TestCase
{
    /**
     * @dataProvider printRoutes
     */
    public function test_print_page_returns_to_tv_guide_after_print_dialog_closes(string $route): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([], 200),
        ]);

        $this->get($route)
            ->assertOk()
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('if (hasReturnedToTvGuide)', false)
            ->assertSee('window.location.replace("\\/tv")', false)
            ->assertSee('window.print()', false)
            ->assertSee('{ once: true }', false);
    }

    public function printRoutes(): array
    {
        return [
            'Ringerike print page' => ['/print'],
            'Ilseng print page' => ['/print-ilseng'],
        ];
    }
}
