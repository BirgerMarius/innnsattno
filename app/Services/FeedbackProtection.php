<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class FeedbackProtection
{
    public const TOKEN_HOURS = 24;
    public const DUPLICATE_SECONDS = 300;

    public function token(Request $request, string $form, ?string $prison = null): string
    {
        return Crypt::encryptString(json_encode([
            'session' => $request->session()->getId(), 'form' => $form,
            'prison' => $prison, 'issued' => now()->timestamp,
        ]));
    }

    public function validateToken(Request $request, string $form, ?string $prison = null): void
    {
        try {
            $token = $request->input('form_token');
            $data = is_string($token) ? json_decode(Crypt::decryptString($token), true) : null;
        } catch (DecryptException $exception) {
            $data = null;
        }

        if (! is_array($data) || ($data['session'] ?? null) !== $request->session()->getId()
            || ($data['form'] ?? null) !== $form || ($data['prison'] ?? null) !== $prison
            || ! is_int($data['issued'] ?? null) || $data['issued'] > now()->timestamp
            || $data['issued'] < now()->subHours(self::TOKEN_HOURS)->timestamp) {
            throw ValidationException::withMessages([
                'form_token' => 'Skjemaet er utløpt eller ugyldig. Prøv å sende på nytt. Teksten er beholdt.',
            ]);
        }
    }

    public function once(Request $request, string $form, ?string $prison, array $values, callable $store)
    {
        // Normalize line endings and surrounding whitespace only; do not change content or case.
        array_walk($values, function (&$value) {
            if (is_string($value)) {
                $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
            }
        });
        $key = 'feedback:'.hash_hmac('sha256', json_encode([
            $request->session()->getId(), $form, $prison, $values,
        ]), config('app.key'));

        try {
            return Cache::lock($key.':lock', 30)->block(5, function () use ($key, $store) {
                if (Cache::has($key)) {
                    return;
                }
                $result = $store();
                Cache::put($key, true, self::DUPLICATE_SECONDS);

                return $result;
            });
        } catch (LockTimeoutException $exception) {
            throw ValidationException::withMessages([
                'form_token' => 'Innsendingen behandles fortsatt. Vent litt og prøv igjen. Teksten er beholdt.',
            ]);
        }
    }
}
