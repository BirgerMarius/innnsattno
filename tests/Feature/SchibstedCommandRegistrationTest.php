<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchibstedCommandRegistrationTest extends TestCase
{
    /** @test */
    public function schibsted_commands_are_registered(): void
    {
        $commands = array_keys(Artisan::all());

        $this->assertContains('football:schibsted-discover', $commands);
        $this->assertContains('football:schibsted-explore', $commands);
        $this->assertContains('football:schibsted-probe', $commands);
    }
}
