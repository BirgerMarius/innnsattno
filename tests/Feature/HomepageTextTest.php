<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTextTest extends TestCase
{
    private const OFFICER_TRIBUTE = 'Hver dag bidrar fengselsbetjenter til trygghet, håp og nye muligheter – med profesjonalitet, menneskelighet og mot gjør dere en uvurderlig forskjell for hele samfunnet.';

    public function testHomepageContainsReorganizedActionsAndNoOfficerTribute(): void
    {
        $response = $this->get(route('tv'));

        $response
            ->assertOk()
            ->assertSee('Skriv ut TV-guide – Ringerike fengsel')
            ->assertSee('href="/print"', false)
            ->assertSee('Bønnetider – Ringerike fengsel')
            ->assertSee('href="/bonnetider"', false)
            ->assertSee('Værmelding – Tyristrand/Ringerike fengsel')
            ->assertSee('href="'.route('weather.index').'"', false)
            ->assertSee('Skriv ut TV-guide – Ilseng fengsel')
            ->assertSee('href="/print-ilseng"', false)
            ->assertSee('Bønnetider – Ilseng fengsel')
            ->assertSee('href="/bonnetider-ilseng"', false)
            ->assertSee('href="'.route('weather.ilseng').'"', false)
            ->assertSee('Værmelding – Ilseng fengsel')
            ->assertSee('href="'.route('visitation.index').'"', false)
            ->assertSee('ℹ️ Ringerike fengsel')
            ->assertSee('ℹ️ Ilseng fengsel')
            ->assertSee('class="prison-actions-placeholder"', false)
            ->assertDontSee('prison-actions-heading')
            ->assertSee('href="/oppdrag"', false)
            ->assertSee('Spinn hjulet')
            ->assertSee('href="'.route('feedback.create').'"', false)
            ->assertSee('Har du en idé?')
            ->assertDontSee(self::OFFICER_TRIBUTE);

        $this->assertSame(1, substr_count((string) $response->getContent(), 'Visitasjonsrullett'));
        $response->assertDontSee('Visitasjonsrullett – Ilseng');
        $this->assertMatchesRegularExpression(
            '/Værmelding – Ilseng fengsel.*prison-actions-placeholder.*ℹ️ Ilseng fengsel/s',
            (string) $response->getContent()
        );
    }
}
