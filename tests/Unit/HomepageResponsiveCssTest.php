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

    public function testLocalNewsRemainsVisibleAndMovesIntoDocumentFlowWhenNeeded(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.local-news-column\s*\{[^}]*display:\s*block;[^}]*max-width:\s*100%;[^}]*position:\s*static;[^}]*width:\s*100%;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1200px\)\s*\{\s*\.front-page-primary-area\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*260px minmax\(0, 1fr\);/s',
            $this->css
        );
        $this->assertStringNotContainsString('@media (min-width: 1888px)', $this->css);
    }

    public function testTestActionsHaveTheirOwnSemanticColour(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.front-page-btn--test\s*\{[^}]*--front-page-btn-bg:\s*#5b6f35;[^}]*--front-page-btn-color:\s*#fff;/s',
            $this->css
        );
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
