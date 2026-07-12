<?php

namespace App\Services\FootballApi\Contracts;

interface FootballProviderInterface
{
    public function get(string $endpoint, array $query = []): array;
}
