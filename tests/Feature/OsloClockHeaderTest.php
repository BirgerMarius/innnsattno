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
        $this->assertStringContainsString('aria-label="Gå til forsiden"', $header);
        $this->assertStringContainsString('class="site-logo"', $header);
    }

    public function testSharedLayoutUsesTheOsloTimezoneAndHidesTheClockWhenPrinting(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $styles = file_get_contents(public_path('css/custom/app.css'));

        $this->assertStringContainsString("timeZone: 'Europe/Oslo'", $layout);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $layout);
        $this->assertStringContainsString('.oslo-clock { display: none !important; }', $styles);
    }
}
