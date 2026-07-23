<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTextTest extends TestCase
{
    private const OFFICER_TRIBUTE = 'Hver dag bidrar fengselsbetjenter til trygghet, håp og nye muligheter – med profesjonalitet, menneskelighet og mot gjør dere en uvurderlig forskjell for hele samfunnet.';

    private const PAPER_SAVING_NOTICE = 'Husk å velge 2 sider per ark og skrive ut dobbeltsidig for å redusere papirforbruket.';

    /**
     * @dataProvider frontPageViewProvider
     */
    public function testFrontPageViewContainsOfficerTributeAndNotPaperSavingNotice(string $view)
    {
        $response = $this->view($view);

        $response
            ->assertSee(self::OFFICER_TRIBUTE)
            ->assertDontSee(self::PAPER_SAVING_NOTICE);

        $this->assertSame(1, substr_count((string) $response, self::OFFICER_TRIBUTE));
    }

    public function frontPageViewProvider(): array
    {
        return [
            'TV guide' => ['tv.guide'],
            'Ilseng TV guide' => ['tv.ilseng'],
        ];
    }
}
