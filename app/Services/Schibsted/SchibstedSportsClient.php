<?php

namespace App\Services\Schibsted;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class SchibstedSportsClient
{
    public const EXIT_HTTP_ERROR = 20;
    public const EXIT_NETWORK_ERROR = 21;
    public const EXIT_INVALID_ARGUMENTS = 22;

    private const SAFE_METHODS = ['GET', 'HEAD'];
    private const REFERENCE_ROOT = 'reference/schibsted/responses';

    public function request(string $path, array $options = []): array
    {
        $method = $this->normalizeMethod($options['method'] ?? 'GET');
        $relativePath = $this->normalizePath($path);
        $timeout = isset($options['timeout']) && $options['timeout'] !== null
            ? (int) $options['timeout']
            : (int) config('services.schibsted_sports.timeout', 10);
        $useCache = (bool) ($options['cache'] ?? false);
        $cacheTtl = (int) config('services.schibsted_sports.cache_ttl', 900);
        $url = $this->url($relativePath);
        $cacheKey = 'schibsted_sports.client.'.sha1($method.' '.$relativePath);

        if ($useCache && $method === 'GET') {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $cached['cached'] = true;

                return $cached;
            }
        }

        $started = microtime(true);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->retry(
                    (int) config('services.schibsted_sports.retry_times', 1),
                    (int) config('services.schibsted_sports.retry_sleep', 250)
                )
                ->withOptions(['allow_redirects' => false])
                ->send($method, $url);
        } catch (ConnectionException $exception) {
            return $this->networkFailure($relativePath, $url, $method, $started, $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->networkFailure($relativePath, $url, $method, $started, $exception->getMessage());
        }

        $result = $this->resultFromResponse($response, $relativePath, $url, $method, $started);

        if ($useCache && $method === 'GET' && $result['is_successful']) {
            Cache::put($cacheKey, $result, now()->addSeconds($cacheTtl));
        }

        return $result;
    }

    public function normalizeMethod(string $method): string
    {
        $method = strtoupper(trim($method));

        if (!in_array($method, self::SAFE_METHODS, true)) {
            throw new InvalidArgumentException('Ugyldig HTTP-metode. Kun GET og HEAD er tillatt.');
        }

        return $method;
    }

    public function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException('API-sti mangler.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new InvalidArgumentException('API-stien inneholder kontrolltegn.');
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) || str_starts_with($path, '//')) {
            throw new InvalidArgumentException('Bruk relativ API-sti, ikke komplett URL.');
        }

        $parts = parse_url($path);

        if (($parts['host'] ?? null) || ($parts['scheme'] ?? null)) {
            throw new InvalidArgumentException('API-stien kan ikke inneholde skjema eller vertsnavn.');
        }

        $cleanPath = '/'.ltrim($parts['path'] ?? $path, '/');

        foreach (explode('/', $cleanPath) as $segment) {
            if ($segment === '..') {
                throw new InvalidArgumentException('API-stien kan ikke inneholde "..".');
            }
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $cleanPath.$query;
    }

    public function url(string $relativePath): string
    {
        return rtrim((string) config('services.schibsted_sports.base_url'), '/').$this->normalizePath($relativePath);
    }

    public function saveResponse(array $result, ?string $output = null): string
    {
        $relative = $this->safeOutputPath($output, $result['path']);
        $fullPath = storage_path('app/'.$relative);
        $directory = dirname($fullPath);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($fullPath, json_encode([
            'metadata' => $this->metadataForStorage($result),
            'body' => $result['json_valid'] ? $result['json'] : $result['body'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        return $fullPath;
    }

    public function safeOutputPath(?string $output, string $requestPath = ''): string
    {
        if ($output === null || trim($output) === '') {
            $name = trim($requestPath, '/');
            $name = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $name) ?: 'response';

            return self::REFERENCE_ROOT.'/'.now()->format('Ymd_His').'_'.substr($name, 0, 120).'.json';
        }

        $output = trim($output);

        if (str_starts_with($output, '/') || str_contains($output, "\0") || str_contains($output, '..')) {
            throw new InvalidArgumentException('Output må være et trygt relativt filnavn under referansemappen.');
        }

        if (!preg_match('/^[A-Za-z0-9_\/.-]+$/', $output)) {
            throw new InvalidArgumentException('Output inneholder ugyldige tegn.');
        }

        $output = ltrim($output, '/');

        if (!str_ends_with($output, '.json')) {
            $output .= '.json';
        }

        if (str_starts_with($output, self::REFERENCE_ROOT.'/')) {
            return $output;
        }

        return self::REFERENCE_ROOT.'/'.$output;
    }

    public function redactMessage(string $message): string
    {
        return preg_replace('/(token|key|secret|cookie|authorization|email)=([^&\s]+)/i', '$1=[redacted]', $message) ?: $message;
    }

    private function resultFromResponse(Response $response, string $path, string $url, string $method, float $started): array
    {
        $body = $response->body();
        $json = null;
        $jsonValid = false;
        $jsonError = null;

        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $json = $decoded;
                $jsonValid = is_array($decoded);
            } catch (Throwable $exception) {
                $jsonError = $exception->getMessage();
            }
        }

        return [
            'ok' => true,
            'network_error' => false,
            'cached' => false,
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'status' => $response->status(),
            'is_successful' => $response->successful(),
            'is_redirect' => $response->redirect(),
            'content_type' => $response->header('content-type'),
            'headers' => $response->headers(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'response_size' => strlen($body),
            'body' => $body,
            'json' => $json,
            'json_valid' => $jsonValid,
            'json_error' => $jsonError,
            'error' => $this->apiError($response, $json, $jsonError),
        ];
    }

    private function networkFailure(string $path, string $url, string $method, float $started, string $message): array
    {
        return [
            'ok' => false,
            'network_error' => true,
            'cached' => false,
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'status' => null,
            'is_successful' => false,
            'is_redirect' => false,
            'content_type' => null,
            'headers' => [],
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'response_size' => 0,
            'body' => '',
            'json' => null,
            'json_valid' => false,
            'json_error' => null,
            'error' => $this->redactMessage($message),
        ];
    }

    private function apiError(Response $response, $json, ?string $jsonError): ?string
    {
        if ($response->successful() && $jsonError === null) {
            return null;
        }

        if (is_array($json)) {
            foreach (['message', 'error', 'detail', 'title'] as $key) {
                if (isset($json[$key]) && is_scalar($json[$key])) {
                    return $this->redactMessage((string) $json[$key]);
                }
            }
        }

        if ($jsonError) {
            return 'Ugyldig JSON: '.$this->redactMessage($jsonError);
        }

        return 'HTTP '.$response->status();
    }

    private function metadataForStorage(array $result): array
    {
        return [
            'method' => $result['method'],
            'path' => $result['path'],
            'url' => $result['url'],
            'status' => $result['status'],
            'content_type' => $result['content_type'],
            'duration_ms' => $result['duration_ms'],
            'response_size' => $result['response_size'],
            'json_valid' => $result['json_valid'],
            'error' => $result['error'],
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
