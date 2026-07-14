<?php

namespace Tests\Unit;

use App\Services\Schibsted\SchibstedSportsClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class SchibstedSportsClientTest extends TestCase
{
    /** @test */
    public function it_validates_relative_paths(): void
    {
        $client = new SchibstedSportsClient();

        $this->assertSame('/tournaments/seasons/7767/schedule', $client->normalizePath('tournaments/seasons/7767/schedule'));
    }

    /** @test */
    public function it_blocks_complete_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SchibstedSportsClient())->normalizePath('https://example.com/tournaments');
    }

    /** @test */
    public function it_blocks_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SchibstedSportsClient())->normalizePath('/tournaments/../seasons');
    }

    /** @test */
    public function it_blocks_invalid_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SchibstedSportsClient())->normalizeMethod('POST');
    }

    /** @test */
    public function it_records_invalid_json(): void
    {
        Http::fake(['*' => Http::response('not-json', 200, ['content-type' => 'application/json'])]);

        $result = (new SchibstedSportsClient())->request('/bad-json');

        $this->assertSame(200, $result['status']);
        $this->assertFalse($result['json_valid']);
        $this->assertStringContainsString('Ugyldig JSON', $result['error']);
    }

    /** @test */
    public function it_records_timeout_or_network_errors(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = (new SchibstedSportsClient())->request('/timeout');

        $this->assertTrue($result['network_error']);
        $this->assertSame('Connection timed out', $result['error']);
    }

    /** @test */
    public function it_writes_only_under_reference_response_directory(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        File::deleteDirectory(storage_path('app/reference/schibsted/responses/test-client'));

        $client = new SchibstedSportsClient();
        $result = $client->request('/ok');
        $path = $client->saveResponse($result, 'test-client/ok');

        $this->assertStringContainsString('/storage/app/reference/schibsted/responses/test-client/ok.json', $path);
        $this->assertFileExists($path);
    }

    /** @test */
    public function it_blocks_output_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SchibstedSportsClient())->safeOutputPath('../outside.json');
    }
}
