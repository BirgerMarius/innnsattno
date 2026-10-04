<?php

namespace Tests\Feature;

use App\Services\FangenyttCoverGenerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerateFangenyttCoversCommandTest extends TestCase
{
    public function testCommandGeneratesCoversForExistingPdfsWithoutDownloadingThem(): void
    {
        Storage::fake('fangenytt');
        Storage::disk('fangenytt')->put('fangenytt-18.pdf', '%PDF-1.4 existing');
        Config::set('fangenytt.issues', [[
            'number' => 18,
            'original_url' => 'https://fangeforeningen.test/fangenytt-18.pdf',
            'local_file' => 'fangenytt-18.pdf',
        ]]);

        $coverGenerator = Mockery::mock(FangenyttCoverGenerator::class);
        $coverGenerator->shouldReceive('generate')
            ->once()
            ->with('fangenytt-18.pdf', 'covers/fangenytt-18.jpg')
            ->andReturn(['success' => true, 'message' => 'covers/fangenytt-18.jpg']);
        $this->app->instance(FangenyttCoverGenerator::class, $coverGenerator);

        $this->artisan('fangenytt:covers')
            ->expectsOutputToContain('forside generert')
            ->assertExitCode(0);
    }
}
