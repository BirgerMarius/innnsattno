<?php

namespace Tests\Feature;

use Tests\TestCase;

class OsloClockHeaderTest extends TestCase
{
    public function testSharedHeaderIncludesTheClockLinkWithoutChangingTheLogoLink(): void
    {
        $header = view('partials.header')->render();

        $this->assertStringContainsString('data-oslo-clock', $header);
        $this->assertStringContainsString('oslo-clock-link', $header);
        $this->assertStringContainsString('target="_blank"', $header);
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
        $this->assertStringContainsString('.oslo-clock__digital { background: #142a42;', $styles);
        $this->assertStringContainsString('color: #fff;', $styles);
        $this->assertStringContainsString('text-shadow: none;', $styles);
        $this->assertStringContainsString('transition: none;', $styles);
        $this->assertStringNotContainsString('.oslo-clock__digital { color: var(--clock-hand);', $styles);
        $this->assertStringContainsString('.oslo-clock__hand--second { display: none; }', $styles);
        $this->assertStringContainsString('.oslo-clock { display: none !important; }', $styles);
        $this->assertStringContainsString('.site-header__message-slot { align-self: center; grid-column: 3;', $styles);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 0; }', $styles);
    }

    public function testHeaderUsesCenteredGridAlignmentAndStacksAllBrandItemsOnNarrowScreens(): void
    {
        $styles = file_get_contents(public_path('css/custom/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.site-header__brand-row\s*\{[^}]*align-items:\s*center;[^}]*grid-template-columns:\s*minmax\(0, 1fr\)\s+minmax\(0, 700px\)\s+minmax\(0, 1fr\);/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.oslo-clock\s*\{[^}]*margin-bottom:\s*0;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*1199\.98px\)\s*\{[^}]*\.site-header__brand-row\s*\{[^}]*grid-template-columns:\s*minmax\(0, 700px\);[^}]*justify-content:\s*center;[^}]*\}[^}]*\.site-header__logo-link,\s*\.oslo-clock-link,\s*\.site-header__message-slot\s*\{[^}]*grid-column:\s*1;/s',
            $styles
        );
        $this->assertStringNotContainsString('.oslo-clock-link { display: none; }', $styles);
    }
}
