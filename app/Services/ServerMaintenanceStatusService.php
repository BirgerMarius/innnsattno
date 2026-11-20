<?php

namespace App\Services;

use App\ServerMaintenanceStatus;
use Carbon\Carbon;

class ServerMaintenanceStatusService
{
    public function readFile(): array
    {
        $path = config('server_maintenance.status_path');
        $errorPath = config('server_maintenance.error_path');
        $error = $this->errorFile($errorPath);
        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) return $error ?: ['state' => 'missing', 'error' => 'Statusfilen er ikke tilgjengelig.'];
        try { $data = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR); } catch (\Throwable $e) { return ['state' => 'error', 'error' => 'Statusfilen er ugyldig JSON.']; }
        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1 || ! in_array($data['status'] ?? null, ['ok', 'error'], true) || ! $this->timestamp($data['checked_at'] ?? null)) return ['state' => 'error', 'error' => 'Statusfilen har ugyldig format.'];
        if ($data['status'] === 'error') return ['state' => 'error', 'checked_at' => Carbon::parse($data['checked_at']), 'error' => $this->text($data['error'] ?? null, 1000) ?: 'Innhentingen feilet.'];
        if (! is_bool($data['reboot_required'] ?? null) || ! is_array($data['packages'] ?? null)) return ['state' => 'error', 'error' => 'Statusfilen mangler pakkedata.'];
        $packages = [];
        foreach ($data['packages'] as $package) {
            if (! is_array($package) || !preg_match('/^[A-Za-z0-9.+:-]+$/', $package['name'] ?? '') || !is_string($package['installed_version'] ?? null) || !is_string($package['candidate_version'] ?? null) || !is_bool($package['held'] ?? null)) return ['state' => 'error', 'error' => 'Statusfilen inneholder ugyldige pakkedata.'];
            if ($package['installed_version'] === $package['candidate_version']) continue;
            $packages[] = ['name' => $package['name'], 'installed_version' => $package['installed_version'], 'candidate_version' => $package['candidate_version'], 'held' => $package['held']];
        }
        usort($packages, fn ($a, $b) => $a['name'] <=> $b['name']);
        $success = ['state' => 'ok', 'checked_at' => Carbon::parse($data['checked_at']), 'packages' => $packages, 'reboot_required' => $data['reboot_required'], 'fingerprint' => hash('sha256', json_encode([$packages, $data['reboot_required']]))];
        return $error && $error['checked_at']->gt($success['checked_at']) ? $error : $success;
    }

    public function dashboard(): array
    {
        $row = ServerMaintenanceStatus::latest('id')->first();
        if (! $row) return ['state' => 'missing', 'detail' => 'Kontrolloppsettet er ikke registrert ennå.', 'packages' => []];
        $stale = $row->last_success_at && $row->last_success_at->lt(now()->subHours(config('server_maintenance.stale_after_hours')));
        $state = $row->state === 'error' ? 'error' : ($stale ? 'stale' : (count($row->packages ?: []) ? 'updates' : 'current'));
        return ['state' => $state, 'detail' => $row->last_error, 'checked_at' => $row->checked_at, 'last_success_at' => $row->last_success_at, 'packages' => $row->packages ?: [], 'reboot_required' => $row->reboot_required];
    }

    private function timestamp($value): bool { try { Carbon::parse($value); return is_string($value); } catch (\Throwable $e) { return false; } }
    private function text($value, int $max): ?string { return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $max) : null; }
    private function errorFile($path): ?array
    {
        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) return null;
        try { $data = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR); } catch (\Throwable $e) { return ['state' => 'error', 'error' => 'Feilstatusfilen er ugyldig JSON.']; }
        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1 || ($data['status'] ?? null) !== 'error' || ! $this->timestamp($data['checked_at'] ?? null)) return ['state' => 'error', 'error' => 'Feilstatusfilen har ugyldig format.'];
        return ['state' => 'error', 'checked_at' => Carbon::parse($data['checked_at']), 'error' => $this->text($data['error'] ?? null, 1000) ?: 'Innhentingen feilet.'];
    }
}
