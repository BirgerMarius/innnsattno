<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionCookieSecurityTest extends TestCase
{
    /**
     * @dataProvider sessionRoutes
     */
    public function test_session_cookie_has_existing_protection_and_secure_attribute_when_enabled(string $route): void
    {
        config()->set('session.driver', 'file');
        config()->set('session.secure', true);

        $response = $this->withSession(['cookie_test' => true])->get($route);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }

    public function sessionRoutes(): array
    {
        return [
            'TV-siden' => ['/tv'],
            'administratorinnloggingen' => ['/admin/login'],
        ];
    }
}
