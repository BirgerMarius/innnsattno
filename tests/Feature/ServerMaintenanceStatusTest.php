<?php

namespace Tests\Feature;

use App\Mail\ServerMaintenanceStatusMail;
use App\ServerMaintenanceMailDelivery;
use App\ServerMaintenanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServerMaintenanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('framework/testing/server-maintenance-'.uniqid().'.json');
        config(['server_maintenance.status_path' => $this->path, 'server_maintenance.enabled' => true, 'server_maintenance.recipient' => 'drift@example.test']);
        Mail::fake();
    }

    protected function tearDown(): void { @unlink($this->path); parent::tearDown(); }

    public function testUpdatesHeldPackagesAndDeduplicatedMailAreRecorded(): void
    {
        $this->write(['status' => 'ok', 'packages' => [['name' => 'nginx', 'installed_version' => '1.0', 'candidate_version' => '1.1', 'held' => true]], 'reboot_required' => true]);
        $this->artisan('server-maintenance:process-status')->assertExitCode(0);
        $this->artisan('server-maintenance:process-status')->assertExitCode(0);
        Mail::assertSent(ServerMaintenanceStatusMail::class, 1);
        $this->assertSame('ok', ServerMaintenanceStatus::first()->state);
        $this->assertTrue(ServerMaintenanceStatus::first()->reboot_required);
        $this->assertTrue(ServerMaintenanceStatus::first()->packages[0]['held']);
    }

    public function testNoUpdatesDoesNotSendMailAndInvalidJsonIsControlError(): void
    {
        $this->write(['status' => 'ok', 'packages' => [], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status')->assertExitCode(0);
        Mail::assertNothingSent();
        file_put_contents($this->path, '{invalid');
        $this->artisan('server-maintenance:process-status')->assertExitCode(0);
        $this->assertSame('error', ServerMaintenanceStatus::first()->state);
        Mail::assertSent(ServerMaintenanceStatusMail::class, 1);
    }

    public function testErrorKeepsLastGoodPackagesAndRecoveryIsSent(): void
    {
        $this->write(['status' => 'ok', 'packages' => [['name' => 'php8.3', 'installed_version' => '8.3.36', 'candidate_version' => '8.3.37', 'held' => false]], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status');
        $this->write(['status' => 'error', 'error' => 'APT failed']);
        $this->artisan('server-maintenance:process-status');
        $this->assertCount(1, ServerMaintenanceStatus::first()->packages);
        $this->write(['status' => 'ok', 'packages' => [], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status');
        Mail::assertSent(ServerMaintenanceStatusMail::class, fn ($mail) => $mail->kind === 'recovered');
    }

    public function testFailedMailRemainsRetryable(): void
    {
        config(['server_maintenance.recipient' => '']);
        $this->write(['status' => 'ok', 'packages' => [['name' => 'nginx', 'installed_version' => '1', 'candidate_version' => '2', 'held' => false]], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status');
        $this->assertNull(ServerMaintenanceMailDelivery::first()->sent_at);
        config(['server_maintenance.recipient' => 'drift@example.test']);
        $this->artisan('server-maintenance:process-status');
        $this->assertNotNull(ServerMaintenanceMailDelivery::first()->fresh()->sent_at);
    }

    public function testDashboardSeparatesMissingAndStaleStatus(): void
    {
        $this->assertSame('missing', app(\App\Services\ServerMaintenanceStatusService::class)->dashboard()['state']);
        ServerMaintenanceStatus::create(['state' => 'ok', 'checked_at' => now()->subDays(2), 'last_success_at' => now()->subDays(2), 'packages' => [], 'reboot_required' => false]);
        $this->assertSame('stale', app(\App\Services\ServerMaintenanceStatusService::class)->dashboard()['state']);
    }

    public function testChangedUpdateInDailyCooldownIsSentTheFollowingDayAndThenWeekly(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-20 10:00:00');
        $this->write(['status' => 'ok', 'packages' => [['name' => 'nginx', 'installed_version' => '1', 'candidate_version' => '2', 'held' => false]], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status');
        $this->write(['status' => 'ok', 'packages' => [['name' => 'nginx', 'installed_version' => '1', 'candidate_version' => '3', 'held' => false]], 'reboot_required' => false]);
        $this->artisan('server-maintenance:process-status');
        Mail::assertSent(ServerMaintenanceStatusMail::class, 1);
        \Carbon\Carbon::setTestNow('2026-11-21 10:00:00');
        $this->artisan('server-maintenance:process-status');
        Mail::assertSent(ServerMaintenanceStatusMail::class, 2);
        \Carbon\Carbon::setTestNow('2026-11-29 10:01:00');
        $this->artisan('server-maintenance:process-status');
        Mail::assertSent(ServerMaintenanceStatusMail::class, fn ($mail) => $mail->kind === 'reminder');
        \Carbon\Carbon::setTestNow();
    }

    private function write(array $data): void
    {
        $data += ['schema_version' => 1, 'checked_at' => now()->toIso8601String()];
        file_put_contents($this->path, json_encode($data));
    }
}
