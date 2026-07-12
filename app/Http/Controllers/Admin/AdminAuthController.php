<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->validCredentials($credentials['email'], $credentials['password'])) {
            return back()
                ->withErrors(['email' => 'Ugyldig e-postadresse eller passord.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        return redirect()->intended(route('admin.professional-resources.index'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function validCredentials(string $email, string $password): bool
    {
        $adminEmail = config('admin.email');
        $passwordHash = config('admin.password_hash');

        if (! $adminEmail || ! $passwordHash) {
            return false;
        }

        return strcasecmp($adminEmail, $email) === 0 && Hash::check($password, $passwordHash);
    }
}
