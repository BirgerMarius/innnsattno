<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalThirdPartyAssetsTest extends TestCase
{
    public function test_shared_layout_uses_local_bootstrap_without_jquery(): void
    {
        $response = $this->get('/oppdrag');

        $response->assertOk()
            ->assertSee('vendor/bootstrap/5.3.7/css/bootstrap.min.css', false)
            ->assertSee('vendor/bootstrap/5.3.7/js/bootstrap.bundle.min.js', false)
            ->assertDontSee('ajax.googleapis.com/ajax/libs/jquery', false)
            ->assertSee('pro.fontawesome.com/releases/v5.13.0/css/all.css', false)
            ->assertSee('www.clarity.ms/tag/', false);

        $this->assertFileExists(public_path('vendor/bootstrap/5.3.7/css/bootstrap.min.css'));
        $this->assertFileExists(public_path('vendor/bootstrap/5.3.7/js/bootstrap.bundle.min.js'));
        $this->assertFileExists(public_path('vendor/bootstrap/5.3.7/LICENSE'));
    }
}
