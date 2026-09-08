<?php

namespace App\Services;

use UnexpectedValueException;

class PrayerTimePayloadException extends UnexpectedValueException
{
    public function __construct(string $message, private array $diagnostics = [])
    {
        parent::__construct($message);
    }

    public function diagnostics(): array
    {
        return $this->diagnostics;
    }
}
