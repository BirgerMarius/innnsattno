<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchibstedExploreCommandTest extends TestCase
{
    /** @test */
    public function explorer_handles_200_and_valid_json(): void
    {
        Http::fake(['*schedule' => Http::response(['events' => [['id' => 1]]], 200)]);

        $this->artisan('football:schibsted-explore', [
            '--path' => '/tournaments/seasons/7767/schedule',
            '--summary' => true,
        ])->expectsOutputToContain('HTTP-status: 200')
            ->expectsOutputToContain('Gyldig JSON: ja')
            ->assertExitCode(0);
    }

    /** @test */
    public function explorer_summary_limits_repeated_list_and_id_output(): void
    {
        $events = [];

        for ($i = 1; $i <= 12; $i++) {
            $events[] = [
                'id' => $i,
                'participantIds' => [$i, $i + 100],
                'tournament' => ['seasonId' => 7767],
            ];
        }

        Http::fake(['*schedule' => Http::response(['events' => $events], 200)]);

        $this->artisan('football:schibsted-explore', [
            '--path' => '/tournaments/seasons/7767/schedule',
            '--summary' => true,
        ])->expectsOutputToContain('events.*.participantIds: 12 forekomster, viser første 5')
            ->expectsOutputToContain('... 7 flere')
            ->expectsOutputToContain('ID-felt: 24 forekomster, viser første 2')
            ->assertExitCode(0);
    }

    /** @test */
    public function explorer_returns_non_zero_for_404(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Not found'], 404)]);

        $this->artisan('football:schibsted-explore', ['--path' => '/missing'])
            ->expectsOutputToContain('HTTP-status: 404')
            ->assertExitCode(20);
    }

    /** @test */
    public function explorer_returns_non_zero_for_500(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

        $this->artisan('football:schibsted-explore', ['--path' => '/error'])
            ->expectsOutputToContain('HTTP-status: 500')
            ->assertExitCode(20);
    }

    /** @test */
    public function explorer_returns_non_zero_for_timeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->artisan('football:schibsted-explore', ['--path' => '/timeout'])
            ->expectsOutputToContain('Nettverksfeil')
            ->assertExitCode(21);
    }

    /** @test */
    public function explorer_limits_body_output(): void
    {
        Http::fake(['*' => Http::response('abcdefghij', 200, ['content-type' => 'text/plain'])]);

        $this->artisan('football:schibsted-explore', [
            '--path' => '/text',
            '--show-body' => true,
            '--max-body' => 4,
        ])->expectsOutputToContain('abcd')
            ->expectsOutputToContain('Body er avkortet')
            ->assertExitCode(0);
    }

    /** @test */
    public function explorer_saves_response_to_safe_output(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        File::delete(storage_path('app/reference/schibsted/responses/tests/explorer.json'));

        $this->artisan('football:schibsted-explore', [
            '--path' => '/ok',
            '--save' => true,
            '--output' => 'tests/explorer',
        ])->assertExitCode(0);

        $this->assertFileExists(storage_path('app/reference/schibsted/responses/tests/explorer.json'));
    }

    /** @test */
    public function explorer_blocks_unsafe_output(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $this->artisan('football:schibsted-explore', [
            '--path' => '/ok',
            '--save' => true,
            '--output' => '../bad',
        ])->assertExitCode(22);
    }
}
