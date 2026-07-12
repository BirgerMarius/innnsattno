<?php

namespace App\Services\FootballApi\Providers;

use App\Services\FootballApi\Contracts\FootballProviderInterface;
use App\Services\FootballApi\FootballApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ApiFootballProvider implements FootballProviderInterface
{
    private string $baseUrl;

    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.football_api.base_url'), '/');
        $this->apiKey = config('services.football_api.key');
    }

    public function get(string $endpoint, array $query = []): array
    {
        if (empty($this->apiKey)) {
            throw new FootballApiException('FOOTBALL_API_KEY is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-apisports-key' => $this->apiKey])
                ->timeout(10)
                ->get($this->baseUrl.'/'.ltrim($endpoint, '/'), $query)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new FootballApiException(
                'Could not connect to API-Football.',
                0,
                $exception
            );
        } catch (RequestException $exception) {
            throw new FootballApiException(
                'API-Football request failed with status '.$exception->response->status().'.',
                0,
                $exception
            );
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new FootballApiException('API-Football returned an invalid response.');
        }

        if (!empty($data['errors'])) {
            throw new FootballApiException('API-Football error: '.$this->formatErrors($data['errors']));
        }

        return $data;
    }

    private function formatErrors($errors): string
    {
        if (is_string($errors)) {
            return $errors;
        }

        if (is_array($errors)) {
            return implode(' ', array_map(function ($error) {
                return is_array($error) ? implode(' ', $error) : (string) $error;
            }, $errors));
        }

        return 'Unknown API error.';
    }
}
