<?php

namespace Tests\Unit;

use App\Services\ChangeHistoryService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChangeHistoryServiceTest extends TestCase
{
    public function testItReadsAllNonMergeCommitsNewestFirstWithoutInternalMetadata(): void
    {
        config(['change-history.repository_path' => base_path()]);
        Cache::flush();

        $history = $this->app->make(ChangeHistoryService::class)->get();

        $this->assertTrue($history['available']);
        $changes = collect($history['groups'])->flatten(1)->values();
        $this->assertNotEmpty($changes);
        $this->assertSame(
            $changes->pluck('date')->sortDesc()->values()->all(),
            $changes->pluck('date')->values()->all()
        );
        $this->assertSame(['date', 'message'], array_keys($changes->first()));
        $this->assertArrayNotHasKey('commit_id', $changes->first());
        $this->assertArrayNotHasKey('author_email', $changes->first());
    }
}
