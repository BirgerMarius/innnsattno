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

    public function testLocalNewsUsesAResponsiveCardGridWithoutTheOldSidebar(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.local-news-grid\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*repeat\(3, minmax\(0, 1fr\)\);/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*1199\.98px\)\s*\{\s*\.local-news-grid\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*767\.98px\).*?\.local-news-grid\s*\{\s*grid-template-columns:\s*1fr;/s',
            $this->css
        );
        $this->assertStringNotContainsString('local-news-column', $this->css);
        $this->assertStringNotContainsString('front-page-primary-area', $this->css);
        $this->assertMatchesRegularExpression(
            '/\.local-news-grid--text\s*\{[^}]*margin-top:\s*16px;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/\.local-news-card--text\s*\{[^}]*box-shadow:\s*none;/s',
            $this->css
        );
        $this->assertStringNotContainsString('local-news-card-image--missing', $this->css);
    }

    public function testTestActionsHaveTheirOwnSemanticColour(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.front-page-btn--test\s*\{[^}]*--front-page-btn-bg:\s*#5b6f35;[^}]*--front-page-btn-color:\s*#fff;/s',
            $this->css
        );
    }

    public function testFootballActionsHaveOneAccessibleSharedColour(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.front-page-btn--football\s*\{[^}]*--front-page-btn-bg:\s*#176b46;[^}]*--front-page-btn-color:\s*#fff;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/\.front-page-btn--football:hover,\s*\.front-page-btn--football:focus\s*\{[^}]*--front-page-btn-bg:\s*#125436;/s',
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
            '/@media\s*\(max-width:\s*767\.98px\).*?\.front-page-grid,\s*\.prison-actions,\s*\.local-news-grid\s*\{\s*grid-template-columns:\s*1fr;/s',
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

    public function testSeasonalArtStaysBehindContentAndIsHiddenOnSmallScreens(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.front-page-theme-art\s*\{[^}]*pointer-events:\s*none;[^}]*position:\s*fixed;[^}]*z-index:\s*0;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/\.front-page-theme\s+\.container\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*1;/s',
            $this->css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*767\.98px\).*?\.front-page-theme-art\s*\{\s*display:\s*none;/s',
            $this->css
        );
    }
}
