<?php

namespace Tests\Feature;

use Tests\TestCase;

class OsloClockHeaderTest extends TestCase
{
    public function testSharedHeaderIncludesTheDecorativeOsloClockWithoutChangingTheLogoLink(): void
    {
        $header = view('partials.header')->render();

        $this->assertStringContainsString('data-oslo-clock', $header);
        $this->assertStringContainsString('aria-hidden="true"', $header);
        $this->assertStringContainsString('site-header__brand-row', $header);
        $this->assertStringContainsString('site-header__message-slot', $header);
        $this->assertStringContainsString('aria-label="Gå til forsiden"', $header);
        $this->assertStringContainsString('class="site-logo"', $header);
    }

    public function testSharedLayoutUsesTheOsloTimezoneAndHidesTheClockWhenPrinting(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $styles = file_get_contents(public_path('css/custom/app.css'));

        $this->assertStringContainsString("timeZone: 'Europe/Oslo'", $layout);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $layout);
        $this->assertStringContainsString("route('header-message.show')", $layout);
        $this->assertStringContainsString("['station', 'midnight', 'metal', 'retro']", $layout);
        $this->assertStringContainsString('30025 - (Date.now() % 30000)', $layout);
        $this->assertStringNotContainsString('reducedMotion ? designs[0]', $layout);
        $this->assertStringContainsString('[data-clock-design="retro"]', $styles);
        $this->assertStringContainsString('.oslo-clock__hand--second { display: none; }', $styles);
        $this->assertStringContainsString('.oslo-clock { display: none !important; }', $styles);
        $this->assertStringContainsString('.site-header__message-slot { grid-column: 1 / -1;', $styles);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 0; }', $styles);
    }
}
