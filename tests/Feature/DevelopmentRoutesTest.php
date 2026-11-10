<?php

namespace Tests\Feature;

use Tests\TestCase;

class DevelopmentRoutesTest extends TestCase
{
    public function test_public_development_routes_are_unavailable_outside_local_environment(): void
    {
        foreach (['/test', '/eliteserien/test', '/premier-league/test'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_design_previews_require_existing_admin_authentication(): void
    {
        $this->get('/admin/design-test')->assertRedirect(route('admin.login'));
        $this->get('/admin/design-test/host')->assertRedirect(route('admin.login'));

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/design-test/host')
            ->assertOk()
            ->assertSee('Designforhåndsvisning');
    }
}
