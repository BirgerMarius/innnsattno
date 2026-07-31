<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HomepageResponsiveCssTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(__DIR__.'/../../public/css/custom/app.css');
    }

    public function testLocalNewsIsHiddenUntilTheEntireSideColumnFits(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.local-news-column\s*\{[^}]*display:\s*none;[^}]*position:\s*absolute;[^}]*right:\s*calc\(100% \+ 24px\);[^}]*width:\s*260px;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1888px\)\s*\{\s*\.local-news-column\s*\{\s*display:\s*block;/s',
            $this->css
        );
        $this->assertStringNotContainsString('@media (min-width: 1500px)', $this->css);
    }

    public function testPrisonColumnsRemainResponsiveWithoutChangingDesktopLayout(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.prison-actions\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*767\.98px\).*?\.front-page-grid,\s*\.prison-actions\s*\{\s*grid-template-columns:\s*1fr;/s',
            $this->css
        );
    }

    public function testDateFieldsCanWrapInsideTheMobileViewport(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*767\.98px\).*?\.front-page-date-item\s*\{[^}]*max-width:\s*100%;[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal;/s',
            $this->css
        );
    }
}
