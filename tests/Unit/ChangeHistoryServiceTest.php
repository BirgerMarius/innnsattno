<?php

namespace Tests\Unit;

use App\Services\ChangeHistoryService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ChangeHistoryServiceTest extends TestCase
{
    private array $temporaryRepositories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryRepositories as $repository) {
            app(Filesystem::class)->deleteDirectory($repository);
        }

        parent::tearDown();
    }

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
        $this->assertContains('Legg til automatisk påminnelse om å stille klokken', $changes->pluck('message'));
    }

    public function testItShowsHistoricalEnglishCommitMessagesInNorwegian(): void
    {
        config(['change-history.repository_path' => base_path()]);
        Cache::flush();

        $history = $this->app->make(ChangeHistoryService::class)->get();
        $messages = collect($history['groups'])->flatten(1)->pluck('message');

        $this->assertContains('Rett tidsperioder for fotballutskrift', $messages);
        $this->assertNotContains('Fix rolling football print windows', $messages);
    }

    public function testItShowsTheTvCacheCorrectionWithPublicFacingDetails(): void
    {
        config(['change-history.repository_path' => base_path()]);
        Cache::flush();

        $history = $this->app->make(ChangeHistoryService::class)->get();
        $changes = collect($history['groups'])->flatten(1);
        $change = $changes->firstWhere('message', 'Mer robust TV-guide: Manglende lokale TV-data bygges automatisk opp igjen, og varsler om manglende data er gjort tydeligere.');

        $this->assertNotNull($change);
        $this->assertSame('2026-10-20', $change['date']->format('Y-m-d'));
    }

    public function testItShowsUnknownNorwegianCommitMessagesWithoutManualRegistration(): void
    {
        $repository = $this->createRepository([
            'Fix rolling football print windows',
            'Legg til automatisk påminnelse om å stille klokken',
        ]);
        config(['change-history.repository_path' => $repository]);
        Cache::flush();

        $messages = collect($this->app->make(ChangeHistoryService::class)->get()['groups'])
            ->flatten(1)
            ->pluck('message');

        $this->assertContains('Legg til automatisk påminnelse om å stille klokken', $messages);
        $this->assertContains('Rett tidsperioder for fotballutskrift', $messages);
    }

    public function testItRefreshesTheCachedHistoryWhenADeploymentChangesHead(): void
    {
        $repository = $this->createRepository(['Første endring']);
        config(['change-history.repository_path' => $repository]);
        Cache::flush();

        $service = $this->app->make(ChangeHistoryService::class);
        $firstHistory = $service->get();
        $this->commit($repository, 'Ny endring etter deploy');
        $secondHistory = $service->get();

        $firstMessages = collect($firstHistory['groups'])->flatten(1)->pluck('message');
        $secondMessages = collect($secondHistory['groups'])->flatten(1)->pluck('message');

        $this->assertNotContains('Ny endring etter deploy', $firstMessages);
        $this->assertContains('Ny endring etter deploy', $secondMessages);
    }

    private function createRepository(array $messages): string
    {
        $repository = sys_get_temp_dir().DIRECTORY_SEPARATOR.'change-history-'.bin2hex(random_bytes(8));
        mkdir($repository, 0700);
        $this->temporaryRepositories[] = $repository;

        $this->runGit(['init', $repository]);
        $this->runGit(['-C', $repository, 'config', 'user.name', 'Change history test']);
        $this->runGit(['-C', $repository, 'config', 'user.email', 'change-history@example.test']);

        foreach ($messages as $message) {
            $this->commit($repository, $message);
        }

        return $repository;
    }

    private function commit(string $repository, string $message): void
    {
        $file = $repository.DIRECTORY_SEPARATOR.'changes.txt';
        file_put_contents($file, $message."\n", FILE_APPEND);
        $this->runGit(['-C', $repository, 'add', 'changes.txt']);
        $this->runGit(['-C', $repository, 'commit', '-m', $message]);
    }

    private function runGit(array $arguments): void
    {
        $process = new Process(array_merge(['git'], $arguments));
        $process->mustRun();
    }
}
