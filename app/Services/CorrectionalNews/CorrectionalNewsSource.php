<?php

namespace App\Services\CorrectionalNews;

interface CorrectionalNewsSource
{
    public function key(): string;
    public function name(): string;
    public function kind(): string;
    public function url(): string;
    public function parse(string $xml): array;
}
