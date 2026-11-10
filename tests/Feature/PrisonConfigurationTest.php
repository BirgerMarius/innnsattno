<?php

namespace Tests\Feature;

use App\Services\PrisonContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrisonConfigurationTest extends TestCase
{
    public function test_active_prison_register_is_valid_without_network_calls(): void
    {
        $this->artisan('prisons:validate')->expectsOutput('Fengselsregisteret er gyldig (3 fengsler).')->assertExitCode(0);
    }

    public function test_validator_reports_missing_required_values(): void
    {
        config()->set('prisons.all.fiktivt-fengsel', [
            'name' => 'Fiktivt fengsel',
            'features' => ['weather' => true, 'local_news' => false, 'tv' => false, 'prayer' => false, 'visitation' => false],
        ]);

        $this->artisan('prisons:validate')
            ->expectsOutput('fiktivt-fengsel: aktiv værmelding krever weather.name samt gyldig latitude og longitude.')
            ->assertExitCode(1);
    }

    public function test_a_fictional_prison_keeps_its_own_context_without_optional_services(): void
    {
        config()->set('prisons.all.fiktivt-fengsel', [
            'name' => 'Fiktivt fengsel',
            'features' => ['weather' => false, 'local_news' => false, 'tv' => false, 'prayer' => false, 'visitation' => false],
        ]);

        $context = app(PrisonContext::class);

        $this->assertSame('/fiktivt-fengsel', $context->homeUrl('fiktivt-fengsel'));
        $this->assertSame('/quiz?prison=fiktivt-fengsel', $context->contextUrl('/quiz', 'fiktivt-fengsel'));
        $this->assertSame([], $context->prison('fiktivt-fengsel')['tv_channels'] ?? []);
        $this->assertFalse($context->prison('fiktivt-fengsel')['features']['local_news']);

        Route::get('/fiktivt-fengsel', fn () => app()->call([
            app(\App\Http\Controllers\FrontPageController::class), 'index',
        ], ['prison' => 'fiktivt-fengsel']));
        Http::fake();

        $this->get('/fiktivt-fengsel')
            ->assertOk()
            ->assertSee('Du er på: Fiktivt fengsel')
            ->assertDontSee('href="/fiktivt-fengsel/vaer"', false)
            ->assertDontSee('href="/fiktivt-fengsel/bonnetider"', false)
            ->assertDontSee('href="/fiktivt-fengsel/tv-utskrift"', false)
            ->assertDontSee('href="/fiktivt-fengsel/visitasjon"', false);
    }

    public function test_prison_route_constraints_are_built_from_the_active_register_and_unknown_ids_are_rejected(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($route) => $route->getName() === 'prison.home');

        $this->assertSame(implode('|', array_map(fn (string $slug) => preg_quote($slug, '#'), array_keys(config('prisons.all')))), $route->wheres['prison']);
        $this->get('/ukjent-fengsel')->assertNotFound();
    }
}
